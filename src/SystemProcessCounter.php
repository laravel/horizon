<?php

namespace Laravel\Horizon;

use Symfony\Component\Process\Process;

class SystemProcessCounter
{
    /**
     * The base command to search for.
     *
     * @var string
     */
    public static $command = 'horizon:work';

    /**
     * Get CPU and memory usage for each Horizon workers for a given supervisor.
     *
     * @param  string  $name
     * @return \Illuminate\Support\Collection
     */
    public function getWorkerStats($name)
    {
        $process = Process::fromShellCommandline('exec ps axo %cpu,%mem,command | grep '.static::$command.' | grep "supervisor='.$name.'" | grep -v "exec ps axo"', null, ['COLUMNS' => '2000']);

        $process->run();

        $rows = explode("\n", $process->getOutput());

        return collect($rows)->filter()->map(function ($rows) {
            $row = collect(explode(' ', $rows))->filter()->take(2);

            return ['cpu' => $row->first() / 100, 'mem' => $row->last()];
        });
    }

    /**
     * Get the number of Horizon workers for a given supervisor.
     *
     * @param  string  $name
     * @return int
     */
    public function get($name)
    {
        return $this->getWorkerStats($name)->count();
    }
}
