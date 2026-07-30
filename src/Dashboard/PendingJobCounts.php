<?php

declare(strict_types=1);

namespace Laravel\Horizon\Dashboard;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Laravel\Horizon\WaitTimeCalculator;
use LogicException;
use Throwable;

final readonly class PendingJobCounts
{
    public function __construct(
        private QueueFactory $queues,
        private WaitTimeCalculator $waitTimes,
    ) {
    }

    /**
     * @param  array<string, int|float>|null  $waits
     * @return array{reserved: ?int, ready: ?int, delayed: ?int, total: ?int}
     */
    public function get(?array $waits = null): array
    {
        try {
            $connections = [];
            $reserved = 0;
            $ready = 0;
            $delayed = 0;

            $supervisedQueues = $this->supervisedQueues(
                $waits ?? $this->waitTimes->calculate(),
            );

            foreach ($supervisedQueues as [$connection, $queueName]) {
                $queue = $connections[$connection] ??= $this->queues->connection($connection);
                $state = $this->pendingState($queue, $queueName);

                $reserved += $state['reserved'];
                $ready += $state['ready'];
                $delayed += $state['delayed'];
            }

            return [
                'reserved' => $reserved,
                'ready' => $ready,
                'delayed' => $delayed,
                'total' => $reserved + $ready + $delayed,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'reserved' => null,
                'ready' => null,
                'delayed' => null,
                'total' => null,
            ];
        }
    }

    public function processing(): bool
    {
        return ($this->get()['reserved'] ?? 0) > 0;
    }

    /**
     * @param  array<string, int|float>  $waits
     * @return list<array{string, string}>
     */
    private function supervisedQueues(array $waits): array
    {
        $queues = [];

        foreach (array_keys($waits) as $supervisedQueue) {
            if (! str_contains($supervisedQueue, ':')) {
                continue;
            }

            [$connection, $queueNames] = explode(':', $supervisedQueue, 2);

            foreach (explode(',', $queueNames) as $queueName) {
                $queueName = trim($queueName);

                if ($connection === '' || $queueName === '') {
                    continue;
                }

                $queues[$connection."\0".$queueName] = [$connection, $queueName];
            }
        }

        return array_values($queues);
    }

    /**
     * @return array{ready: int, reserved: int, delayed: int}
     */
    private function pendingState(object $queue, string $queueName): array
    {
        $callback = [$queue, 'pendingState'];

        if (! is_callable($callback)) {
            throw new LogicException('Queue connection does not support pendingState().');
        }

        $state = $callback($queueName);

        if (! is_array($state)) {
            throw new LogicException('Queue connection returned a non-array pendingState().');
        }

        foreach (['ready', 'reserved', 'delayed'] as $key) {
            if (! array_key_exists($key, $state) || ! is_numeric($state[$key])) {
                throw new LogicException("Queue connection pendingState() is missing a numeric {$key} count.");
            }
        }

        return [
            'ready' => (int) $state['ready'],
            'reserved' => (int) $state['reserved'],
            'delayed' => (int) $state['delayed'],
        ];
    }
}
