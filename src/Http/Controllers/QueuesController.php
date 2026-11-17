<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Http\Request;
use Laravel\Horizon\Contracts\SupervisorRepository;

class QueuesController extends Controller
{
    /**
     * Pause the given queue.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @param  \Laravel\Horizon\Contracts\SupervisorRepository  $supervisors
     * @return void
     */
    public function pause(Request $request, QueueFactory $queue, SupervisorRepository $supervisors)
    {
        [$connection, $name] = $this->validated($request, $queue, $supervisors);

        $queue->pause($connection, $name);
    }

    /**
     * Resume the given queue.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @param  \Laravel\Horizon\Contracts\SupervisorRepository  $supervisors
     * @return void
     */
    public function resume(Request $request, QueueFactory $queue, SupervisorRepository $supervisors)
    {
        [$connection, $name] = $this->validated($request, $queue, $supervisors);

        $queue->resume($connection, $name);
    }

    /**
     * Validate that the requested queue is processed by one of the supervisors.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @param  \Laravel\Horizon\Contracts\SupervisorRepository  $supervisors
     * @return array{0: string, 1: string}
     */
    protected function validated(Request $request, QueueFactory $queue, SupervisorRepository $supervisors)
    {
        abort_unless(method_exists($queue, 'pause'), 501, 'Pausing queues requires Laravel 12.40 or later.');

        $validated = $request->validate([
            'connection' => ['required', 'string'],
            'queue' => ['required', 'string'],
        ]);

        $known = collect($supervisors->all())
            ->flatMap(fn ($supervisor) => array_keys($supervisor->processes ?? []))
            ->flatMap(function ($key) {
                [$connection, $queues] = explode(':', $key, 2);

                return array_map(fn ($queue) => $connection.':'.$queue, explode(',', $queues));
            });

        abort_unless($known->contains($validated['connection'].':'.$validated['queue']), 404);

        return [$validated['connection'], $validated['queue']];
    }
}
