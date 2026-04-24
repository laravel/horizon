<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Laravel\Horizon\Contracts\ProcessRepository;
use Laravel\Horizon\Models\HorizonProcess;

class DatabaseProcessRepository implements ProcessRepository
{
    /**
     * Get all of the orphan process IDs and the times they were observed.
     *
     * @param  string  $master
     * @return array
     */
    public function allOrphans($master)
    {
        return HorizonProcess::where('master', $master)
            ->get()
            ->mapWithKeys(fn ($row) => [$row->process_id => $row->recorded_at->getTimestamp()])
            ->all();
    }

    /**
     * Record the given process IDs as orphaned.
     *
     * @param  string  $master
     * @param  array  $processIds
     * @return void
     */
    public function orphaned($master, array $processIds)
    {
        if (empty($processIds)) {
            HorizonProcess::where('master', $master)->delete();

            return;
        }

        HorizonProcess::where('master', $master)
            ->whereNotIn('process_id', $processIds)
            ->delete();

        $now = CarbonImmutable::now();

        $rows = collect($processIds)->map(fn ($id) => [
            'master' => $master,
            'process_id' => (string) $id,
            'recorded_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        HorizonProcess::insertOrIgnore($rows);
    }

    /**
     * Get the process IDs orphaned for at least the given number of seconds.
     *
     * @param  string  $master
     * @param  int  $seconds
     * @return array
     */
    public function orphanedFor($master, $seconds)
    {
        $threshold = CarbonImmutable::now()->subSeconds($seconds);

        return HorizonProcess::where('master', $master)
            ->where('recorded_at', '<', $threshold)
            ->pluck('process_id')
            ->all();
    }

    /**
     * Remove the given process IDs from the orphan list.
     *
     * @param  string  $master
     * @param  array  $processIds
     * @return void
     */
    public function forgetOrphans($master, array $processIds)
    {
        HorizonProcess::where('master', $master)
            ->whereIn('process_id', $processIds)
            ->delete();
    }
}
