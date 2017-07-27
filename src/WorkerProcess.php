<?php

namespace Laravel\Horizon;

use Closure;
use Cake\Chronos\Chronos;
use Symfony\Component\Process\Process;
use Laravel\Horizon\Events\UnableToLaunchProcess;
use Laravel\Horizon\Events\WorkerProcessRestarting;

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
     * @var \Cake\Chronos\Chronos
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
        if ($this->process->isRunning()) {
            $this->process->signal(SIGUSR2);
        }
    }

    /**
     * Instruct the worker process to continue working.
     *
     * @return void
     */
    public function continue()
    {
        if ($this->process->isRunning()) {
            $this->process->signal(SIGCONT);
        }
    }

    /**
     * Evaluate the current state of the process.
     *
     * @return void
     */
    public function monitor()
    {
        if ($this->process->isRunning() || $this->coolingDown()) {
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

        // Here we will reset the cooldown period timestamp since we are attempting to
        // restart the process again. We will start the process and give it several
        // milliseconds to get started up before we check its new running status.
        $this->restartAgainAt = null;

        $this->start($this->output);

        usleep(250 * 1000);

        // If the process is still not running after giving it some time, we will just
        // begin the cooldown period so we do not try to restart this process again
        // too soon and overwhelm the application's log or error handling layers.
        if (! $this->process->isRunning()) {
            $this->cooldown();
        }
    }

    /**
     * Terminate the underlying process.
     *
     * @return void
     */
    public function terminate()
    {
        if ($this->process->isRunning()) {
            $this->process->signal(SIGTERM);
        }
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
     * Begin the cool-down period for the process.
     *
     * @return void
     */
    protected function cooldown()
    {
        $this->restartAgainAt = Chronos::now()->addMinutes(1);

        event(new UnableToLaunchProcess($this));
    }

    /**
     * Determine if the process is cooling down from a failed restart.
     *
     * @return bool
     */
    public function coolingDown()
    {
        return isset($this->restartAgainAt) &&
               Chronos::now()->lt($this->restartAgainAt);
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
