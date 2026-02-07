<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Http\Requests\PauseQueueRequest;
use Laravel\Horizon\Http\Requests\ResumeQueueRequest;

class QueueController extends Controller
{
    /**
     * Pause a specific queue.
     *
     * @param  \Laravel\Horizon\Http\Requests\PauseQueueRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pause(PauseQueueRequest $request)
    {
        $connection = $request->input('connection');
        $queue = $request->input('queue');

        Queue::pause($connection, $queue);

        return response()->json([
            'status' => 'success',
            'message' => "Queue {$connection}:{$queue} has been paused.",
        ]);
    }

    /**
     * Resume a paused queue.
     *
     * @param  \Laravel\Horizon\Http\Requests\ResumeQueueRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resume(ResumeQueueRequest $request)
    {
        $connection = $request->input('connection');
        $queue = $request->input('queue');

        Queue::resume($connection, $queue);

        return response()->json([
            'status' => 'success',
            'message' => "Queue {$connection}:{$queue} has been resumed.",
        ]);
    }

    /**
     * Check if a queue is paused.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @return \Illuminate\Http\JsonResponse
     */
    public function status($connection, $queue)
    {
        $isPaused = Queue::isPaused($connection, $queue);

        return response()->json([
            'is_paused' => $isPaused,
            'connection' => $connection,
            'queue' => $queue,
        ]);
    }
}
