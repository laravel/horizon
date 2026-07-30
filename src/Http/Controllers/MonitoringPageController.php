<?php

declare(strict_types=1);

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Support\HorizonScrollMetadata;

final class MonitoringPageController extends Controller
{
    public function __construct(
        private readonly TagRepository $tags,
        private readonly JobRepository $jobs,
    ) {
        parent::__construct();
    }

    public function index(): Response
    {
        return Inertia::render('monitoring', [
            'tags' => fn (): array => $this->monitoredTags(),
        ]);
    }

    public function jobs(Request $request, string $tag): Response
    {
        return $this->tagJobsPage(
            $request,
            $tag,
            'monitoring/tag-jobs',
            $tag,
            false,
        );
    }

    public function failed(Request $request, string $tag): Response
    {
        return $this->tagJobsPage(
            $request,
            $tag,
            'monitoring/tag-jobs',
            'failed:'.$tag,
            true,
        );
    }

    /**
     * @return list<array{tag: string, count: int}>
     */
    private function monitoredTags(): array
    {
        return collect($this->tags->monitoring())
            ->map(fn (string $tag): array => [
                'tag' => $tag,
                'count' => $this->tags->count($tag) + $this->tags->count('failed:'.$tag),
            ])
            ->sortBy('tag')
            ->values()
            ->all();
    }

    private function tagJobsPage(
        Request $request,
        string $tag,
        string $component,
        string $storageTag,
        bool $failed,
    ): Response {
        $startingAt = max(-1, $request->integer('starting_at', -1));
        $perPage = 50;
        $total = (int) $this->tags->count($storageTag);
        $resolveJobs = fn () => once(
            fn (): Collection => $this->jobs
                ->getJobs(
                    $this->tags->paginate($storageTag, $startingAt + 1, $perPage),
                    $startingAt + 1,
                )
                ->map(function (object $job): object {
                    $job->payload = json_decode($job->payload);

                    return $job;
                })
                ->values(),
        );

        return Inertia::render($component, [
            'tag' => $tag,
            'failed' => $failed,
            'listRevision' => fn (): string => json_encode([
                $total,
                $resolveJobs()->first()?->id,
            ], JSON_THROW_ON_ERROR),
            'jobs' => Inertia::scroll(
                fn (): array => ['data' => $resolveJobs()],
                'data',
                fn (array $_value): HorizonScrollMetadata => new HorizonScrollMetadata(
                    'starting_at',
                    null,
                    $startingAt + $perPage + 1 < $total
                        ? $startingAt + $perPage
                        : null,
                    $startingAt,
                ),
            )->matchOn('data.id'),
            'total' => $total,
        ]);
    }
}
