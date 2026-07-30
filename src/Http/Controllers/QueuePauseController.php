<?php

declare(strict_types=1);

namespace Laravel\Horizon\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Queue\QueueManager;
use Laravel\Horizon\Http\Requests\PauseQueueRequest;
use Laravel\Horizon\Http\Requests\ResumeQueueRequest;
use Laravel\Horizon\Support\FrameworkCapabilities;

final class QueuePauseController extends Controller
{
    /**
     * Pause a queue.
     *
     * @param  \Laravel\Horizon\Http\Requests\PauseQueueRequest  $request
     * @param  \Illuminate\Queue\QueueManager  $queues
     * @return \Illuminate\Http\Response
     */
    public function store(
        PauseQueueRequest $request,
        QueueManager $queues,
        FrameworkCapabilities $capabilities,
    ): Response {
        $capabilities->ensureQueuePausing();
        $data = $request->validatedData();

        if ($data['duration_minutes'] !== null && $capabilities->queuePauseFor) {
            $until = CarbonImmutable::now()->addMinutes($data['duration_minutes']);

            $queues->pauseFor($data['connection'], $data['queue'], $until);
        } else {
            $queues->pause($data['connection'], $data['queue']);
        }

        return response()->noContent();
    }

    /**
     * Resume a queue.
     *
     * @param  \Laravel\Horizon\Http\Requests\ResumeQueueRequest  $request
     * @param  \Illuminate\Queue\QueueManager  $queues
     * @return \Illuminate\Http\Response
     */
    public function destroy(
        ResumeQueueRequest $request,
        QueueManager $queues,
        FrameworkCapabilities $capabilities,
    ): Response {
        $capabilities->ensureQueuePausing();
        $data = $request->validatedData();

        $queues->resume($data['connection'], $data['queue']);

        return response()->noContent();
    }
}
