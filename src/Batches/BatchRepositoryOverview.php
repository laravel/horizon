<?php

declare(strict_types=1);

namespace Laravel\Horizon\Batches;

use Illuminate\Bus\Batch;
use Illuminate\Bus\BatchRepository;
use Throwable;

/**
 * Bounded, request-local batch overview for navigation counts and dashboard stats.
 *
 * Requests one more than the display limit. Exact totals/active counts are only
 * returned when that short page is not full. Missing database batch storage is
 * treated as an expected unavailable capability without reporting.
 */
final readonly class BatchRepositoryOverview
{
    private const PAGE_LIMIT = 50;

    public function __construct(
        private BatchRepository $batches,
        private DatabaseBatchCapability $capability,
    ) {
    }

    /**
     * @return array{
     *     total: ?int,
     *     active: ?int,
     *     previews: list<array{id: string, name: string, progress: int}>
     * }
     */
    public function get(): array
    {
        return once(function (): array {
            if (! $this->capability->available()) {
                return $this->unavailable();
            }

            try {
                $retained = $this->batches->get(self::PAGE_LIMIT + 1, null);
            } catch (Throwable $exception) {
                $this->reportUnexpected($exception);

                return $this->unavailable();
            }

            $complete = count($retained) <= self::PAGE_LIMIT;
            $active = 0;
            /** @var list<array{id: string, name: string, progress: int}> $previews */
            $previews = [];

            foreach (array_slice($retained, 0, self::PAGE_LIMIT) as $batch) {
                if (! $batch instanceof Batch
                    || $batch->cancelled()
                    || max(0, $batch->pendingJobs - $batch->failedJobs) === 0
                ) {
                    continue;
                }

                $active++;

                if (count($previews) >= 3) {
                    continue;
                }

                $name = trim($batch->name);
                $previews[] = [
                    'id' => $batch->id,
                    'name' => $name === '' ? $batch->id : $name,
                    'progress' => (int) round($batch->progress()),
                ];
            }

            return [
                'total' => $complete ? count($retained) : null,
                'active' => $complete ? $active : null,
                'previews' => $previews,
            ];
        });
    }

    /**
     * @return array{
     *     total: null,
     *     active: null,
     *     previews: list<never>
     * }
     */
    private function unavailable(): array
    {
        return [
            'total' => null,
            'active' => null,
            'previews' => [],
        ];
    }

    private function reportUnexpected(Throwable $exception): void
    {
        try {
            report($exception);
        } catch (Throwable) {
            // Unit tests may not have a bound exception handler.
        }
    }
}
