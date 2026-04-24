<?php

namespace Laravel\Horizon;

use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\HorizonCommandQueue;
use Laravel\Horizon\Models\HorizonCommand;

class DatabaseHorizonCommandQueue implements HorizonCommandQueue
{
    /**
     * Push a command onto a given queue.
     *
     * @param  string  $name
     * @param  string  $command
     * @param  array  $options
     * @return void
     */
    public function push($name, $command, array $options = [])
    {
        HorizonCommand::create([
            'queue' => $name,
            'command' => $command,
            'options' => $options,
        ]);
    }

    /**
     * Get the pending commands for a given queue name.
     *
     * @param  string  $name
     * @return array
     */
    public function pending($name)
    {
        return DB::transaction(function () use ($name) {
            $commands = HorizonCommand::where('queue', $name)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($commands->isEmpty()) {
                return [];
            }

            HorizonCommand::whereIn('id', $commands->pluck('id'))->delete();

            return $commands
                ->map(fn ($row) => (object) [
                    'command' => $row->command,
                    'options' => $row->options ?? [],
                ])
                ->all();
        });
    }

    /**
     * Flush the command queue for a given queue name.
     *
     * @param  string  $name
     * @return void
     */
    public function flush($name)
    {
        HorizonCommand::where('queue', $name)->delete();
    }
}
