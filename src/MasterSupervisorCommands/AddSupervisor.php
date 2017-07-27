<?php

namespace Laravel\Horizon\MasterSupervisorCommands;

use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\SupervisorOptions;
use Laravel\Horizon\SupervisorProcess;
use Symfony\Component\Process\Process;

class AddSupervisor
{
    /**
     * Process the command.
     *
     * @param \Laravel\Horizon\MasterSupervisor $master
     * @param array $options
     * @return void
     */
    public function process(MasterSupervisor $master, array $options)
    {
        $options = SupervisorOptions::fromArray($options);

        $master->supervisors[] = $process = new SupervisorProcess(
            $options, $this->createProcess($master, $options), function ($type, $line) use ($master) {
                $master->output($type, $line);
            }
        );
    }

    /**
     * Create the Symfony process instance.
     *
     * @param \Laravel\Horizon\MasterSupervisor $master
     * @param \Laravel\Horizon\SupervisorOptions $options
     * @return $this
     */
    protected function createProcess(MasterSupervisor $master, SupervisorOptions $options)
    {
        $command = $options->toSupervisorCommand();

        return (new Process($command, $options->directory ?? null))
                    ->setTimeout(null)
                    ->disableOutput();
    }
}
