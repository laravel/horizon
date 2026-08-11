<?php

declare(strict_types=1);

namespace Laravel\Horizon\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Laravel\Horizon\Contracts\MetricsRepository;
use Throwable;

final class MetricController extends Controller
{
    public function __construct(private readonly MetricsRepository $metrics)
    {
        parent::__construct();
    }

    public function show(string $type, string $name): Response
    {
        try {
            $snapshots = $this->snapshots($type, $name);
            $preview = [
                'data' => $snapshots,
                'available' => true,
                'message' => null,
            ];
        } catch (Throwable $exception) {
            report($exception);

            $preview = [
                'data' => [],
                'available' => false,
                'message' => "Metrics for this {$this->singular($type)} are currently unavailable.",
            ];
        }

        return Inertia::render('metrics/show', [
            'type' => $type,
            'name' => $name,
            'preview' => $preview,
        ]);
    }

    /**
     * @return list<array{timestamp: int, throughput: int, runtime: ?float}>
     */
    private function snapshots(string $type, string $name): array
    {
        $snapshots = $type === 'jobs'
            ? $this->metrics->snapshotsForJob($name)
            : $this->metrics->snapshotsForQueue($name);

        return collect($snapshots)
            ->map($this->normalize(...))
            ->filter()
            ->sortBy('timestamp')
            ->values()
            ->all();
    }

    /**
     * @return array{timestamp: int, throughput: int, runtime: ?float}|null
     */
    private function normalize(mixed $snapshot): ?array
    {
        if (is_object($snapshot)) {
            $snapshot = (array) $snapshot;
        }

        if (! is_array($snapshot)) {
            return null;
        }

        $timestamp = $snapshot['time'] ?? null;
        $throughput = $snapshot['throughput'] ?? null;
        $runtime = $snapshot['runtime'] ?? null;

        if (
            ! is_numeric($timestamp)
            || ($throughput !== null && ! is_numeric($throughput))
            || ($runtime !== null && ! is_numeric($runtime))
        ) {
            return null;
        }

        return [
            'timestamp' => (int) $timestamp,
            'throughput' => $throughput === null ? 0 : (int) $throughput,
            'runtime' => $runtime === null ? null : round((float) $runtime / 1000, 3),
        ];
    }

    private function singular(string $type): string
    {
        return $type === 'jobs' ? 'job' : 'queue';
    }
}
