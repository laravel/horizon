<?php

namespace Laravel\Horizon;

use Closure;
use Exception;
use Throwable;
use Cake\Chronos\Chronos;
use Laravel\Horizon\Contracts\Pausable;
use Laravel\Horizon\Contracts\Terminable;
use Laravel\Horizon\Contracts\Restartable;
use Laravel\Horizon\Events\SupervisorLooped;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Laravel\Horizon\Contracts\HorizonCommandQueue;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Supervisor implements Pausable, Restartable, Terminable
{
    use ListensForSignals;

    /**
     * The name of this supervisor instance.
     *
     * @return string
     */
    public $name;

    /**
     * The SupervisorOptions that should be utilized.
     *
     * @var \Laravel\Horizon\SupervisorOptions
     */
    public $options;

    /**
     * All of the process pools being managed.
     *
     * @var \Illuminate\Support\Collection
     */
    public $processPools;

    /**
     * Indicates if the Supervisor processes are working.
     *
     * @var bool
     */
    public $working = true;

    /**
     * The time at which auto-scaling last ran for this supervisor.
     *
     * @var \Cake\Chronos\Chronos
     */
    public $lastAutoScaled;

    /**
     * The number of seconds to wait in between auto-scaling attempts.
     *
     * @var int
     */
    public $autoScaleCooldown = 3;

    /**
     * The output handler.
     *
     * @var \Closure|null
     */
    public $output;

    /**
     * Create a new supervisor instance.
     *
     * @param  \Laravel\Horizon\SupervisorOptions  $options
     * @return void
     */
    public function __construct(SupervisorOptions $options)
    {
        $this->options = $options;
        $this->name = $options->name;
        $this->processPools = $this->createProcessPools();

        $this->output = function () {
            //
        };

        app(HorizonCommandQueue::class)->flush($this->name);
    }

    /**
     * Create the supervisor's process pools.
     *
     * @return \Illuminate\Support\Collection
     */
    public function createProcessPools()
    {
        return $this->options->balancing()
                        ? $this->createProcessPoolPerQueue()
                        : $this->createSingleProcessPool();
    }

    /**
     * Create a process pool for each queue.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function createProcessPoolPerQueue()
    {
        return collect(explode(',', $this->options->queue))->map(function ($queue) {
            return $this->createProcessPool($this->options->withQueue($queue));
        });
    }

    /**
     * Create a single process pool.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function createSingleProcessPool()
    {
        return collect([$this->createProcessPool($this->options)]);
    }

    /**
     * Create a new process pool with the given options.
     *
     * @param  \Laravel\Horizon\SupervisorOptions  $options
     * @return \Laravel\Horizon\ProcessPool
     */
    protected function createProcessPool(SupervisorOptions $options)
    {
        return new ProcessPool($options, function ($type, $line) {
            $this->output($type, $line);
        });
    }

    /**
     * Scale the process count.
     *
     * @param  int  $processes
     * @return void
     */
    public function scale($processes)
    {
        $this->options->maxProcesses = max($this->options->maxProcesses,
            $processes, count($this->processPools)
        );

        $this->balance($this->processPools->mapWithKeys(function ($pool) use ($processes) {
            return [$pool->queue() => floor($processes / count($this->processPools))];
        })->all());
    }

    /**
     * Balance the process pool at the given scales.
     *
     * @param  array  $balance
     * @return void
     */
    public function balance(array $balance)
    {
        foreach ($balance as $queue => $scale) {
            $this->processPools->first(function ($pool) use ($queue) {
                return $pool->queue() === $queue;
            }, new class {
                public function __call($method, $arguments)
                {
                }
            })->scale($scale);
        }
    }

    /**
     * Terminate all current workers and start fresh ones.
     *
     * @return void
     */
    public function restart()
    {
        $this->working = true;

        $this->processPools->each->restart();
    }

    /**
     * Pause all of the worker processes.
     *
     * @return void
     */
    public function pause()
    {
        $this->working = false;

        $this->processPools->each->pause();
    }

    /**
     * Instruct all of the worker processes to continue working.
     *
     * @return void
     */
    public function continue()
    {
        $this->working = true;

        $this->processPools->each->continue();
    }

    /**
     * Terminate this supervisor process and all of its workers.
     *
     * @param  int  $status
     * @return void
     */
    public function terminate($status = 0)
    {
        $this->working = false;

        // We will mark this supervisor as terminating so that any user interface can
        // correctly show the supervisor's status. Then, we will scale the process
        // pools down to zero workers to gracefully terminate them all out here.
        app(SupervisorRepository::class)->forget($this->name);

        $this->processPools->each(function ($pool) {
            $pool->processes()->each(function ($process) {
                $process->terminate();
            });
        });

        if ($this->shouldWait()) {
            while ($this->processPools->map->runningProcesses()->collapse()->count()) {
                sleep(1);
            }
        }

        $this->exit($status);
    }

    /**
     * Check if the supervisor should wait for all its workers to terminate.
     *
     * @return bool
     */
    protected function shouldWait()
    {
        return ! config('horizon.fast_termination') ||
               app(CacheFactory::class)->get('horizon:terminate:wait');
    }

    /**
     * Monitor the worker processes.
     *
     * @return void
     */
    public function monitor()
    {
        $this->ensureNoDuplicateSupervisors();

        $this->listenForSignals();

        $this->persist();

        while (true) {
            sleep(1);

            $this->loop();
        }
    }

    /**
     * Ensure no other supervisors are running with the same name.
     *
     * @return void
     * @throws \Exception
     */
    public function ensureNoDuplicateSupervisors()
    {
        if (app(SupervisorRepository::class)->find($this->name) !== null) {
            throw new Exception("A supervisor with the name [{$this->name}] is already running.");
        }
    }

    /**
     * Perform a monitor loop.
     *
     * @return void
     */
    public function loop()
    {
        try {
            $this->processPendingSignals();

            $this->processPendingCommands();

            // If the supervisor is working, we will perform any needed scaling operations and
            // monitor all of these underlying worker processes to make sure they are still
            // processing queued jobs. If they have died, we will restart them each here.
            if ($this->working) {
                $this->autoScale();

                $this->processPools->each->monitor();
            }

            // Next, we'll persist the supervisor state to storage so that it can be read by a
            // user interface. This contains information on the specific options for it and
            // the current number of worker processes per queue for easy load monitoring.
            $this->persist();

            event(new SupervisorLooped($this));
        } catch (Exception $e) {
            app(ExceptionHandler::class)->report($e);
        } catch (Throwable $e) {
            app(ExceptionHandler::class)->report(new FatalThrowableError($e));
        }
    }

    /**
     * Handle any pending commands for the supervisor.
     *
     * @return void
     */
    protected function processPendingCommands()
    {
        foreach (app(HorizonCommandQueue::class)->pending($this->name) as $command) {
            app($command->command)->process($this, $command->options);
        }
    }

    /**
     * Run the auto-scaling routine for the supervisor.
     *
     * @return void
     */
    protected function autoScale()
    {
        $this->lastAutoScaled = $this->lastAutoScaled ?:
                    Chronos::now()->subSeconds($this->autoScaleCooldown + 1);

        if (Chronos::now()->subSeconds($this->autoScaleCooldown)->gte($this->lastAutoScaled)) {
            $this->lastAutoScaled = Chronos::now();

            app(AutoScaler::class)->scale($this);
        }
    }

    /**
     * Persist information about this supervisor instance.
     *
     * @return void
     */
    public function persist()
    {
        app(SupervisorRepository::class)->update($this);
    }

    /**
     * Prune all terminating processes and return the total process count.
     *
     * @return int
     */
    public function pruneAndGetTotalProcesses()
    {
        $this->pruneTerminatingProcesses();

        return $this->totalProcessCount();
    }

    /**
     * Prune any terminating processes that have finished terminating.
     *
     * @return void
     */
    public function pruneTerminatingProcesses()
    {
        $this->processPools->each->pruneTerminatingProcesses();
    }

    /**
     * Get all of the current processes as a collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function processes()
    {
        return $this->processPools->map->processes()->collapse();
    }

    /**
     * Get the processes that are still terminating.
     *
     * @return \Illuminate\Support\Collection
     */
    public function terminatingProcesses()
    {
        return $this->processPools->map->terminatingProcesses()->collapse();
    }

    /**
     * Get the total active process count, including processes pending termination.
     *
     * @return int
     */
    public function totalProcessCount()
    {
        return $this->processPools->sum->totalProcessCount();
    }

    /**
     * Get CPU and memory usage for the active workers by asking the OS.
     *
     * @return \Illuminate\Support\Collection
     */
    public function workerStats()
    {
        return app(SystemProcessCounter::class)->getWorkerStats($this->name);
    }

    /**
     * Get the total active process count by asking the OS.
     *
     * @return int
     */
    public function totalSystemProcessCount()
    {
        return app(SystemProcessCounter::class)->get($this->name);
    }

    /**
     * Get the process ID for this supervisor.
     *
     * @return int
     */
    public function pid()
    {
        return getmypid();
    }

    /**
     * Get the current memory usage (in megabytes).
     *
     * @return float
     */
    public function memoryUsage()
    {
        return memory_get_usage() / 1024 / 1024;
    }

    /**
     * Determine if the supervisor is paused.
     *
     * @return bool
     */
    public function isPaused()
    {
        return ! $this->working;
    }

    /**
     * Set the output handler.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function handleOutputUsing(Closure $callback)
    {
        $this->output = $callback;

        return $this;
    }

    /**
     * Handle the given output.
     *
     * @param  string  $type
     * @param  string  $line
     * @return void
     */
    public function output($type, $line)
    {
        call_user_func($this->output, $type, $line);
    }

    /**
     * Shutdown the supervisor.
     *
     * @param  int  $status
     * @return void
     */
    protected function exit($status = 0)
    {
        $this->exitProcess($status);
    }

    /**
     * Exit the PHP process.
     *
     * @param  int  $status
     * @return void
     */
    protected function exitProcess($status = 0)
    {
        exit((int) $status);
    }
}
