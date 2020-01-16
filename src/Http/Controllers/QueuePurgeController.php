<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Repositories\RedisJobRepository;

class QueuePurgeController extends Controller
{
    public function __invoke(RedisJobRepository $jobRepository)
    {
        $jobRepository->purge($queue = request('queue'));

        Redis::connection(config('horizon.use'))->del("queues:{$queue}");
    }
}
