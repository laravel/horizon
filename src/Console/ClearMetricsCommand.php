<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Laravel\Horizon\Contracts\MetricsRepository;

class ClearMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:clear-metrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete metrics for all jobs and queues';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Horizon\Contracts\MetricsRepository  $metrics
     * @return void
     */
    public function handle(MetricsRepository $metrics)
    {
        $metrics->clear();

        $this->info('Metrics cleared successfully.');
    }
}
