<?php

declare(strict_types=1);

namespace Laravel\Horizon\Support;

use Laravel\Horizon\Batches\BatchRepositoryOverview;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\TagRepository;

final readonly class NavigationCounts
{
    public function __construct(
        private JobRepository $jobs,
        private TagRepository $tags,
        private MetricsRepository $metrics,
        private BatchRepositoryOverview $batches,
    ) {
    }

    /**
     * @return array{
     *     monitoring: int,
     *     metrics: int,
     *     batches: ?int,
     *     pending: int,
     *     completed: int,
     *     silenced: int,
     *     failed: int
     * }
     */
    public function get(): array
    {
        return [
            'monitoring' => count($this->tags->monitoring()),
            'metrics' => count($this->metrics->measuredJobs()) + count($this->metrics->measuredQueues()),
            'batches' => $this->batches->get()['total'],
            'pending' => $this->jobs->countPending(),
            'completed' => $this->jobs->countCompleted(),
            'silenced' => $this->jobs->countSilenced(),
            'failed' => $this->jobs->countFailed(),
        ];
    }
}
