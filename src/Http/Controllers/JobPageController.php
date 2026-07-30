<?php

declare(strict_types=1);

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Dashboard\JobDetails;
use Laravel\Horizon\Support\HorizonScrollMetadata;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class JobPageController extends Controller
{
    public function __construct(
        private readonly JobRepository $jobs,
        private readonly TagRepository $tags,
        private readonly JobDetails $jobDetails,
    ) {
        parent::__construct();
    }

    public function pending(Request $request): Response
    {
        return $this->page(
            $request,
            'jobs/pending',
            fn (int $afterIndex): Collection => $this->jobs->getPending($afterIndex),
            (int) $this->jobs->countPending(),
        );
    }

    public function completed(Request $request): Response
    {
        return $this->page(
            $request,
            'jobs/completed',
            fn (int $afterIndex): Collection => $this->jobs->getCompleted($afterIndex),
            (int) $this->jobs->countCompleted(),
        );
    }

    public function failed(Request $request): Response
    {
        $tag = (string) $request->query('tag', '');

        if ($tag === '') {
            return $this->page(
                $request,
                'jobs/failed',
                fn (int $afterIndex): Collection => $this->jobs->getFailed($afterIndex),
                (int) $this->jobs->countFailed(),
                ['tag' => ''],
            );
        }

        return $this->page(
            $request,
            'jobs/failed',
            fn (int $afterIndex): Collection => $this->jobs->getJobs(
                $this->tags->paginate('failed:'.$tag, $afterIndex + 1, 50),
                $afterIndex + 1,
            ),
            (int) $this->tags->count('failed:'.$tag),
            ['tag' => $tag],
        );
    }

    public function silenced(Request $request): Response
    {
        return $this->page(
            $request,
            'jobs/silenced',
            fn (int $afterIndex): Collection => $this->jobs->getSilenced($afterIndex),
            (int) $this->jobs->countSilenced(),
        );
    }

    public function show(string $type, string $id): Response|SymfonyResponse
    {
        $job = $this->jobDetails->find($type, $id);

        if (is_null($job)) {
            return $this->unavailable('jobs/show', [
                'type' => $type,
                'job' => null,
            ]);
        }

        return Inertia::render('jobs/show', [
            'type' => $type,
            'job' => $job,
        ]);
    }

    /**
     * Render the detail shell when a retained record is missing or pruned.
     *
     * Document visits keep a truthful 404. Inertia XHR visits return 200 so
     * client navigations and polling can replace the page with the empty state
     * without treating a still-valid Inertia payload as an HTTP exception.
     *
     * @param  array<string, mixed>  $props
     */
    private function unavailable(string $component, array $props): Response|SymfonyResponse
    {
        $page = Inertia::render($component, $props);

        if (request()->inertia()) {
            return $page;
        }

        return $page->toResponse(request())->setStatusCode(404);
    }

    /**
     * @param  \Closure(int): Collection<int, object>  $jobs
     * @param  array<string, mixed>  $props
     */
    private function page(
        Request $request,
        string $component,
        \Closure $jobs,
        int $total,
        array $props = [],
    ): Response {
        $startingAt = max(-1, $request->integer('starting_at', -1));
        $perPage = 50;
        $resolveJobs = fn () => once(
            fn (): Collection => $jobs($startingAt)
                ->map(function (object $job): object {
                    $job->payload = json_decode($job->payload);

                    return $job;
                })
                ->values(),
        );

        return Inertia::render($component, [
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
            ...$props,
        ]);
    }
}
