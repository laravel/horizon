<?php

namespace Laravel\Horizon;

use Closure;
use Laravel\Horizon\Contracts\HorizonCommandQueue;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\MasterSupervisorCommands\AddSupervisor;
use Laravel\Horizon\SupervisorCommands\Terminate;

class SupervisorProcess extends WorkerProcess
{
    /**
     * The name of the supervisor.
     *
     * @var string
     */
    public $name;

    /**
     * The supervisor process options.
     *
     * @var \Laravel\Horizon\SupervisorOptions
     */
    public $options;

    /**
     * Indicates if the process is "dead".
     *
     * @var bool
     */
    public $dead = false;

    /**
     * The exit codes on which supervisor should be marked as dead.
     *
     * @var array
     */
    public $dontRestartOn = [
        0,
        2,
        13, // Indicates duplicate supervisors...
    ];

    /**
     * Create a new supervisor process instance.
     *
     * @param  \Laravel\Horizon\SupervisorOptions  $options
     * @param  \Symfony\Component\Process\Process  $process
     * @param  \Closure|null  $output
     * @return void
     */
    public function __construct(SupervisorOptions $options, $process, Closure $output = null)
    {
        $this->options = $options;
        $this->name = $options->name;

        $this->output = $output ?: function () {
            //
        };

        parent::__construct($process);
    }

    /**
     * Evaluate the current state of the process.
     *
     * @return void
     */
    public function monitor()
    {
        if (! $this->process->isStarted()) {
            return $this->restart();
        }

        // First, we will check to see if the supervisor failed as a duplicate and if
        // it did we will go ahead and mark it as dead. We will do this before the
        // other checks run because we do not care if this is cooling down here.
        if (! $this->process->isRunning() &&
            $this->process->getExitCode() === 13) {
            return $this->markAsDead();
        }

        // If the process is running or cooling down from a failure, we don't need to
        // attempt to do anything right now, so we can just bail out of the method
        // here and it will get checked out during the next master monitor loop.
        if ($this->process->isRunning() ||
            $this->coolingDown()) {
            return;
        }

        // Next, we will determine if the exit code is one that means this supervisor
        // should be marked as dead and not be restarted. Typically, this could be
        // an indication that the supervisor was simply purposefully terminated.
        $exitCode = $this->process->getExitCode();

        $this->markAsDead();

        // If the supervisor exited with a status code that we do not restart on then
        // we will not attempt to restart it. Otherwise, we will need to provision
        // it back out based on the latest provisioning information we have now.
        if (in_array($exitCode, $this->dontRestartOn)) {
            return;
        }

        $this->reprovision();
    }

    /**
     * Re-provision this supervisor process based on the provisioning plan.
     *
     * @return void
     */
    protected function reprovision()
    {
        if (isset($this->name)) {
            app(SupervisorRepository::class)->forget($this->name);
        }

        app(HorizonCommandQueue::class)->push(
            MasterSupervisor::commandQueue(),
            AddSupervisor::class,
            $this->options->toArray()
        );
    }

    /**
     * Terminate the supervisor with the given status.
     *
     * @param  int  $status
     * @return void
     */
    public function terminateWithStatus($status)
    {
        app(HorizonCommandQueue::class)->push(
            $this->options->name, Terminate::class, ['status' => $status]
        );
    }

    /**
     * Mark the process as "dead".
     *
     * @return void
     */
    protected function markAsDead()
    {
        $this->dead = true;
    }
}
