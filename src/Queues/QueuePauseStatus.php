<?php

declare(strict_types=1);

namespace Laravel\Horizon\Queues;

use Illuminate\Queue\QueueManager;
use Laravel\Horizon\Support\FrameworkCapabilities;

final readonly class QueuePauseStatus
{
    public function __construct(
        private QueueManager $queues,
        private FrameworkCapabilities $capabilities,
    ) {
    }

    /**
     * Pause state for a queue.
     *
     * Laravel's pauseFor() stores a boolean with a TTL, not a readable deadline.
     * Without Horizon-owned pause metadata, pausedUntil is always unavailable.
     *
     * @return array{paused: bool, pausedUntil: null}
     */
    public function for(string $connection, string $queue): array
    {
        if (! $this->capabilities->queuePausing) {
            return ['paused' => false, 'pausedUntil' => null];
        }

        if (! $this->queues->isPaused($connection, $queue)) {
            return ['paused' => false, 'pausedUntil' => null];
        }

        return [
            'paused' => true,
            'pausedUntil' => null,
        ];
    }
}
