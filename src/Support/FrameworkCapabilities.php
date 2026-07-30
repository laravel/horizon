<?php

declare(strict_types=1);

namespace Laravel\Horizon\Support;

use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;
use LogicException;
use ReflectionClass;

final readonly class FrameworkCapabilities
{
    public function __construct(
        public bool $queuePausing,
        public bool $queuePauseFor = false,
    ) {
    }

    /**
     * Detect the capabilities supplied by the installed framework.
     *
     * Basic queue pausing requires indefinite pause APIs plus active worker
     * pause polling. Timed pausing additionally requires pauseFor().
     */
    public static function detect(): self
    {
        $queueManager = new ReflectionClass(QueueManager::class);

        $queuePausing = $queueManager->hasMethod('pause')
            && $queueManager->hasMethod('resume')
            && $queueManager->hasMethod('isPaused')
            && self::workerPausePollingEnabled();

        $queuePauseFor = $queuePausing && $queueManager->hasMethod('pauseFor');

        return new self(
            queuePausing: $queuePausing,
            queuePauseFor: $queuePauseFor,
        );
    }

    /**
     * Ensure queue pausing is supported.
     */
    public function ensureQueuePausing(): void
    {
        if (! $this->queuePausing) {
            throw new LogicException('Queue pausing is not supported by the installed Laravel version.');
        }
    }

    /**
     * Get the capabilities as an array.
     *
     * @return array{queuePausing: bool, queuePauseFor: bool}
     */
    public function toArray(): array
    {
        return [
            'queuePausing' => $this->queuePausing,
            'queuePauseFor' => $this->queuePauseFor,
        ];
    }

    /**
     * Determine whether workers will poll for paused queues.
     *
     * Laravel Cloud and withoutInterruptionPolling() disable worker pause checks.
     */
    private static function workerPausePollingEnabled(): bool
    {
        if (! property_exists(Worker::class, 'pausable')) {
            return true;
        }

        return Worker::$pausable;
    }
}
