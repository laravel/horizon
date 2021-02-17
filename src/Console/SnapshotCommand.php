<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Lock;

class SnapshotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:snapshot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store a snapshot of the queue metrics';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Horizon\Lock  $lock
     * @param  \Laravel\Horizon\Contracts\MetricsRepository  $metrics
     * @return void
     */
    public function handle(Lock $lock, MetricsRepository $metrics)
    {
        if ($lock->get('metrics:snapshot', config('horizon.metrics.snapshot_lock', 300) - 30)) {
            $metrics->snapshot();

            $this->info('Metrics snapshot stored successfully.');
        }
    }
}
