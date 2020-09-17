<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Arr;
use Laravel\Horizon\Repositories\RedisJobRepository;

class QueueClearController extends Controller
{
    /**
     * Clear the specified queue.
     *
     * @param  \Laravel\Horizon\Repositories\RedisJobRepository  $jobRepository
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __invoke(RedisJobRepository $jobRepository, QueueManager $manager, Request $request)
    {
        $jobRepository->purge($queue = $request->input('queue'));
        
        $connection = Arr::first(config('horizon.defaults'))['connection'] ?? 'redis';
        $manager->connection($connection)->clear($queue);
    }
}
