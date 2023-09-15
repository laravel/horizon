<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Laravel\Horizon\Contracts\MetricsRepository;

class ResetMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:reset-metrics';

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
        $metrics->reset();

        $this->info('Metrics reset successfully.');
    }
}
