<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionResolverInterface;
use Laravel\Horizon\Contracts\ProcessRepository;

class DatabaseProcessRepository implements ProcessRepository
{
    /**
     * The database connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    public $resolver;

    /**
     * Create a new repository instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public function __construct(ConnectionResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Get all of the orphan process IDs and the times they were observed.
     *
     * @param  string  $master
     * @return array
     */
    public function allOrphans($master)
    {
        return $this->table()
            ->where('master', $master)
            ->pluck('recorded_at', 'process_id')
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
        $time = CarbonImmutable::now()->getTimestamp();
        $processIds = array_map('strval', $processIds);

        $existing = $this->table()
            ->where('master', $master)
            ->pluck('process_id')
            ->all();

        $shouldRemove = array_diff($existing, $processIds);

        if (! empty($shouldRemove)) {
            $this->table()
                ->where('master', $master)
                ->whereIn('process_id', $shouldRemove)
                ->delete();
        }

        $shouldInsert = array_diff($processIds, $existing);

        foreach ($shouldInsert as $processId) {
            $this->table()->insert([
                'master' => $master,
                'process_id' => $processId,
                'recorded_at' => $time,
            ]);
        }
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
        $expiresAt = CarbonImmutable::now()->getTimestamp() - $seconds;

        return $this->table()
            ->where('master', $master)
            ->where('recorded_at', '<', $expiresAt)
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
        $this->table()
            ->where('master', $master)
            ->whereIn('process_id', array_map('strval', $processIds))
            ->delete();
    }

    /**
     * Get a query builder for the horizon processes table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->connection()->table('horizon_processes');
    }

    /**
     * Get the database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return $this->resolver->connection(config('horizon.database.connection'));
    }
}
