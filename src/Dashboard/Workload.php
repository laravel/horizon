<?php

declare(strict_types=1);

namespace Laravel\Horizon\Dashboard;

use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Queues\QueuePauseStatus;
use Throwable;

final readonly class Workload
{
    public function __construct(
        private WorkloadRepository $workload,
        private QueuePauseStatus $queuePauseStatus,
        private MetricsRepository $metrics,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function get(): array
    {
        return collect($this->workload->get())
            ->map(function (array $queue): array {
                $connection = $this->resolvedConnection($queue['connection'] ?? null);
                $queue['connection'] = $connection;
                $splitQueues = $queue['split_queues'] ?? null;

                if ($splitQueues !== null) {
                    $queue['paused'] = false;
                    $queue['pausedUntil'] = null;
                    $queue['split_queues'] = collect($splitQueues)
                        ->map(function (array $splitQueue) use ($connection): array {
                            $name = $splitQueue['name'] ?? '';

                            return [
                                ...$splitQueue,
                                ...$this->pauseState($connection, $name),
                                'throughput' => $this->throughputForQueue($name),
                            ];
                        })
                        ->values()
                        ->all();

                    $queue['throughput'] = $this->sumChildThroughput($queue['split_queues']);
                } else {
                    $name = $queue['name'] ?? '';

                    $queue = [
                        ...$queue,
                        ...$this->pauseState($connection, $name),
                        'throughput' => $this->throughputForQueue($name),
                    ];
                }

                return $queue;
            })
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @return non-empty-string|null
     */
    private function resolvedConnection(mixed $connection): ?string
    {
        if (! is_string($connection) || $connection === '') {
            return null;
        }

        return $connection;
    }

    /**
     * @return array{paused: bool, pausedUntil: null|int}
     */
    private function pauseState(?string $connection, string $queue): array
    {
        if ($connection === null) {
            return [
                'paused' => false,
                'pausedUntil' => null,
            ];
        }

        return $this->queuePauseStatus->for($connection, $queue);
    }

    /**
     * Throughput for a single queue name. Never look up comma-joined group names.
     * Metric failures and non-numeric results are surfaced as null so the table stays usable.
     */
    private function throughputForQueue(string $queue): ?int
    {
        if ($queue === '' || str_contains($queue, ',')) {
            return null;
        }

        try {
            $throughput = $this->metrics->throughputForQueue($queue);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        if (! is_numeric($throughput)) {
            return null;
        }

        return (int) $throughput;
    }

    /**
     * Parent throughput is the sum of children only when every child value is available.
     * An empty child list is unavailable (null), never a fabricated zero.
     *
     * @param  list<array<string, mixed>>  $splitQueues
     */
    private function sumChildThroughput(array $splitQueues): ?int
    {
        if ($splitQueues === []) {
            return null;
        }

        $total = 0;

        foreach ($splitQueues as $splitQueue) {
            $throughput = $splitQueue['throughput'] ?? null;

            if (! is_int($throughput)) {
                return null;
            }

            $total += $throughput;
        }

        return $total;
    }
}
