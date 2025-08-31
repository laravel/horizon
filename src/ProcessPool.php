<?php

namespace Laravel\Horizon;

use Carbon\CarbonImmutable;
use Closure;
use Countable;
use Symfony\Component\Process\Process;

class ProcessPool implements Countable
{
    /**
     * All of the active processes.
     *
     * @var array
     */
    public $processes = [];

    /**
     * The processes that are terminating.
     *
     * @var array
     */
    public $terminatingProcesses = [];

    /**
     * Indicates if the process pool is currently running.
     *
     * @var array
     */
    public $working = true;

    /**
     * The supervisor options for the process pool.
     *
     * @var SupervisorOptions
     */
    public $options;

    /**
     * The output handler.
     *
     * @var \Closure|null
     */
    public $output;

    /**
     * Create a new process pool instance.
     *
     * @param  \Laravel\Horizon\SupervisorOptions  $options
     * @param  \Closure|null  $output
     * @return void
     */
    public function __construct(SupervisorOptions $options, ?Closure $output = null)
    {
        $this->options = $options;

        $this->output = $output ?: function () {
            //
        };
    }

    /**
     * Scale the process count.
     *
     * @param  int  $processes
     * @return void
     */
    public function scale($processes)
    {
        $processes = max(0, (int) $processes);

        if ($processes === count($this->processes)) {
            return;
        }

        if ($processes > count($this->processes)) {
            $this->scaleUp($processes);
        } else {
            $this->scaleDown($processes);
        }
    }

    /**
     * Scale up to the given number of processes.
     *
     * @param  int  $processes
     * @return void
     */
    protected function scaleUp($processes)
    {
        $difference = $processes - count($this->processes);

        for ($i = 0; $i < $difference; $i++) {
            $this->start();
        }
    }

    /**
     * Scale down to the given number of processes.
     *
     * @param  int  $processes
     * @return void
     */
    protected function scaleDown($processes)
    {
        $difference = count($this->processes) - $processes;

        // Here we will slice off the correct number of processes that we need to terminate
        // and remove them from the active process array. We'll be adding them the array
        // of terminating processes where they'll run until they are fully terminated.
        $terminatingProcesses = array_slice(
            $this->processes, 0, $difference
        );

        collect($terminatingProcesses)
            ->each(function ($process) {
                $this->markForTermination($process);
            })
            ->all();

        $this->removeProcesses($difference);

        // Finally we will call the terminate method on each of the processes that need get
        // terminated so they can start terminating. Terminating is a graceful operation
        // so any jobs they are already running will finish running before these quit.
        collect($this->terminatingProcesses)
                        ->each(function ($process) {
                            $process['process']->terminate();
                        });
    }

    /**
     * Mark the given worker process for termination.
     *
     * @param  \Laravel\Horizon\WorkerProcess  $process
     * @return void
     */
    public function markForTermination(WorkerProcess $process)
    {
        $this->terminatingProcesses[] = [
            'process' => $process, 'terminatedAt' => CarbonImmutable::now(),
        ];
    }

    /**
     * Remove the given number of processes from the process array.
     *
     * @param  int  $count
     * @return void
     */
    protected function removeProcesses($count)
    {
        array_splice($this->processes, 0, $count);

        $this->processes = array_values($this->processes);
    }

    /**
     * Add a new worker process to the pool.
     *
     * @return $this
     */
    protected function start()
    {
        $this->processes[] = $this->createProcess()->handleOutputUsing(function ($type, $line) {
            call_user_func($this->output, $type, $line);
        });

        return $this;
    }

    /**
     * Create a new process instance.
     *
     * @return \Laravel\Horizon\WorkerProcess
     */
    protected function createProcess()
    {
        $class = config('horizon.fast_termination')
            ? BackgroundProcess::class
            : Process::class;

        return new WorkerProcess($class::fromShellCommandline(
            $this->options->toWorkerCommand(), $this->options->directory
        )->setTimeout(null)->disableOutput());
    }

    /**
     * Evaluate the current state of all of the processes.
     *
     * @return void
     */
    public function monitor()
    {
        $this->processes()->each->monitor();
    }

    /**
     * Terminate all current workers and start fresh ones.
     *
     * @return void
     */
    public function restart()
    {
        $count = count($this->processes);

        $this->scale(0);

        $this->scale($count);
    }

    /**
     * Pause all of the worker processes.
     *
     * @return void
     */
    public function pause()
    {
        $this->working = false;

        collect($this->processes)->each->pause();
    }

    /**
     * Instruct all of the worker processes to continue working.
     *
     * @return void
     */
    public function continue()
    {
        $this->working = true;

        collect($this->processes)->each->continue();
    }

    /**
     * Get the processes that are still terminating.
     *
     * @return \Illuminate\Support\Collection
     */
    public function terminatingProcesses()
    {
        $this->pruneTerminatingProcesses();

        return collect($this->terminatingProcesses);
    }

    /**
     * Remove any non-running processes from the terminating process list.
     *
     * @return void
     */
    public function pruneTerminatingProcesses()
    {
        $this->stopTerminatingProcessesThatAreHanging();

        $this->terminatingProcesses = collect($this->terminatingProcesses)
            ->filter(fn ($process) => $process['process']->isRunning())
            ->all();
    }

    /**
     * Stop any terminating processes that are hanging too long.
     *
     * @return void
     */
    protected function stopTerminatingProcessesThatAreHanging()
    {
        foreach ($this->terminatingProcesses as $process) {
            $timeout = $this->options->timeout;

            if ($process['terminatedAt']->addSeconds((int) $timeout)->lte(CarbonImmutable::now())) {
                $process['process']->stop();
            }
        }
    }

    /**
     * Get all of the current processes as a collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function processes()
    {
        return collect($this->processes);
    }

    /**
     * Get all of the current running processes as a collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function runningProcesses()
    {
        $terminatingProcesses = $this->terminatingProcesses()->map(function ($process) {
            return $process['process'];
        });

        return collect($this->processes)
            ->concat($terminatingProcesses)
            ->filter(fn ($process) => $process->process->isRunning());
    }

    /**
     * Get the total active process count, including processes pending termination.
     *
     * @return int
     */
    public function totalProcessCount()
    {
        return count($this->processes()) + count($this->terminatingProcesses);
    }

    /**
     * The name of the queue(s) being worked by the pool.
     *
     * @return string
     */
    public function queue()
    {
        return $this->options->queue;
    }

    /**
     * Count the total number of processes in the pool.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->processes);
    }
}
