<?php

namespace Laravel\Horizon;

use Carbon\CarbonImmutable;
use Closure;
use Laravel\Horizon\Events\UnableToLaunchProcess;
use Laravel\Horizon\Events\WorkerProcessRestarting;
use Symfony\Component\Process\Exception\ExceptionInterface;

class WorkerProcess
{
    /**
     * The underlying Symfony process.
     *
     * @var \Symfony\Component\Process\Process
     */
    public $process;

    /**
     * The output handler callback.
     *
     * @var \Closure
     */
    public $output;

    /**
     * The time at which the cooldown period will be over.
     *
     * @var \Carbon\CarbonImmutable
     */
    public $restartAgainAt;

    /**
     * Create a new worker process instance.
     *
     * @param  \Symfony\Component\Process\Process  $process
     * @return void
     */
    public function __construct($process)
    {
        $this->process = $process;
    }

    /**
     * Start the process.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function start(Closure $callback)
    {
        $this->output = $callback;

        $this->cooldown();

        $this->process->start($callback);

        return $this;
    }

    /**
     * Pause the worker process.
     *
     * @return void
     */
    public function pause()
    {
        $this->sendSignal(SIGUSR2);
    }

    /**
     * Instruct the worker process to continue working.
     *
     * @return void
     */
    public function continue()
    {
        $this->sendSignal(SIGCONT);
    }

    /**
     * Evaluate the current state of the process.
     *
     * @return void
     */
    public function monitor()
    {
        if ($this->process->isRunning() || ($this->coolingDown() && $this->process->getExitCode() !== 0)) {
            return;
        }

        $this->restart();
    }

    /**
     * Restart the process.
     *
     * @return void
     */
    protected function restart()
    {
        if ($this->process->isStarted()) {
            event(new WorkerProcessRestarting($this));
        }

        $this->start($this->output);
    }

    /**
     * Terminate the underlying process.
     *
     * @return void
     */
    public function terminate()
    {
        $this->sendSignal(SIGTERM);
    }

    /**
     * Stop the underlying process.
     *
     * @return void
     */
    public function stop()
    {
        if ($this->process->isRunning()) {
            $this->process->stop();
        }
    }

    /**
     * Send a POSIX signal to the process.
     *
     * @param  int  $signal
     * @return void
     */
    protected function sendSignal($signal)
    {
        try {
            $this->process->signal($signal);
        } catch (ExceptionInterface $e) {
            if ($this->process->isRunning()) {
                throw $e;
            }
        }
    }

    /**
     * Begin the cool-down period for the process.
     *
     * @return void
     */
    protected function cooldown()
    {
        if ($this->coolingDown()) {
            return;
        }

        if ($this->restartAgainAt) {
            $this->restartAgainAt = ! $this->process->isRunning()
                            ? CarbonImmutable::now()->addMinute()
                            : null;

            if (! $this->process->isRunning()) {
                event(new UnableToLaunchProcess($this));
            }
        } else {
            $this->restartAgainAt = CarbonImmutable::now()->addSecond();
        }
    }

    /**
     * Determine if the process is cooling down from a failed restart.
     *
     * @return bool
     */
    public function coolingDown()
    {
        return isset($this->restartAgainAt) &&
               CarbonImmutable::now()->lt($this->restartAgainAt);
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
     * Pass on method calls to the underlying process.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->process->{$method}(...$parameters);
    }
}
