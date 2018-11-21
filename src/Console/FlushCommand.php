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
    protected $description = 'Flush Horizon queues';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $redis = app(RedisFactory::class);

        $redis->connection('horizon')->flushdb();

        return $this->info('Horizon queues flushed!');
    }
}
