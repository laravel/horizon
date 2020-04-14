<?php

namespace Laravel\Horizon;

use Illuminate\Support\Arr;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;

class ProcessInspector
{
    /**
     * The command executor.
     *
     * @var \Laravel\Horizon\Exec
     */
    public $exec;

    /**
     * Create a new process inspector instance.
     *
     * @param  \Laravel\Horizon\Exec  $exec
     * @return void
     */
    public function __construct(Exec $exec)
    {
        $this->exec = $exec;
    }

    /**
     * Get the IDs of all Horizon processes running on the system.
     *
     * @return array
     */
    public function current()
    {
        $supervisorBasename = MasterSupervisor::basename();

        return $this->exec->run("pgrep -f '[h]orizon.*[ =]{$supervisorBasename}-'");
    }

    /**
     * Get an array of running Horizon processes that can't be accounted for.
     *
     * @return array
     */
    public function orphaned()
    {
        return array_diff(
            array_merge($this->current(), $this->mastersWithoutSupervisors()),
            $this->validMonitoring()
        );
    }

    /**
     * Get all of the process IDs that Horizon is actively monitoring and that are valid.
     *
     * For master processes "valid" means they have child processes (supervisors).
     *
     * For supervisors "valid" means their parent processes (masters) exist.
     *
     * @return array
     */
    public function validMonitoring()
    {
        $masters = $this->monitoredMastersWithSupervisors();

        $masterNames = array_flip(Arr::pluck($masters, 'name'));

        return collect(app(SupervisorRepository::class)->all())
            ->filter(function ($supervisor) use (&$masterNames) {
                return isset($masterNames[data_get($supervisor, 'master')]);
            })
            ->pluck('pid')
            ->pipe(function ($processes) {
                $processes->each(function ($process) use (&$processes) {
                    $processes = $processes->merge($this->exec->run("pgrep -P {$process}"));
                });

                return $processes;
            })
            ->merge(Arr::pluck($masters, 'pid'))
            ->all();
    }

    /**
     * Get the master processes that have child processes (supervisors) and are monitored by Horizon.
     *
     * @return array
     */
    public function monitoredMastersWithSupervisors()
    {
        return collect(app(MasterSupervisorRepository::class)->all())->filter(function ($master) {
            return ! empty($this->exec->run('pgrep -P '.data_get($master, 'pid')));
        })->values()->all();
    }

    /**
     * Get the IDs of all master Horizon processes that don't have any supervisors.
     *
     * @return array
     */
    public function mastersWithoutSupervisors()
    {
        return collect($this->exec->run('pgrep -f [h]orizon$'))->filter(function ($pid) {
            return empty($this->exec->run('pgrep -P '.$pid));
        })->values()->all();
    }
}
