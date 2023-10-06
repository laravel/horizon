<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Redis\Factory as RedisFactory;

class FlushCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Truncate all horizon-related information from Redis";

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Horizon\Contracts\MetricsRepository  $metrics
     * @return void
     */
    public function handle(RedisFactory $redis)
    {
      if(app()->environment('local')) {
        $redis->connection('horizon')->client()->flushAll();
        $this->info('All queue jobs flushed successfully.');
      }
    }
}
