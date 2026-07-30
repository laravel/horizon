<?php

declare(strict_types=1);

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Bus\Batch;
use Illuminate\Bus\BatchRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Horizon\Batches\BatchPresentation;
use Laravel\Horizon\Support\HorizonScrollMetadata;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

final class BatchPageController extends Controller
{
    private const PAGE_SIZE = 50;

    public function __construct(
        private readonly BatchRepository $batches,
        private readonly BatchPresentation $presentation,
    ) {
        parent::__construct();
    }

    public function index(Request $request): Response
    {
        $beforeId = trim((string) $request->query('before_id', '')) ?: null;
        $resolvePage = fn () => once(fn (): array => $this->page($beforeId));

        return Inertia::render('batches', [
            'batches' => Inertia::scroll(
                function () use ($resolvePage): array {
                    $page = $resolvePage();

                    return ['data' => $page['items']];
                },
                'data',
                function (array $_value) use ($resolvePage): HorizonScrollMetadata {
                    $page = $resolvePage();

                    return new HorizonScrollMetadata(
                        'before_id',
                        null,
                        $page['next'],
                        $page['current'],
                    );
                },
            )->matchOn('data.id'),
        ]);
    }

    public function show(string $id): Response|SymfonyResponse
    {
        $batch = $this->batches->find($id);

        if (is_null($batch)) {
            return $this->unavailable('batches/show', [
                'batch' => null,
                'failedJobs' => [],
                'failedJobsComplete' => true,
            ]);
        }

        $failed = $this->presentation->failedJobs($batch);

        return Inertia::render('batches/show', [
            'batch' => $this->presentation->summary($batch),
            'failedJobs' => $failed['rows'],
            'failedJobsComplete' => $failed['complete'],
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
     * @return array{items: array<int, mixed>, current: ?string, next: ?string}
     */
    private function page(?string $beforeId): array
    {
        try {
            $batches = $this->batches->get(self::PAGE_SIZE + 1, $beforeId);
            $slice = array_values(array_slice($batches, 0, self::PAGE_SIZE));
            $items = array_map(
                function (mixed $batch): mixed {
                    return $batch instanceof Batch
                        ? $this->presentation->summary($batch)
                        : $batch;
                },
                $slice,
            );
            $boundary = $slice === [] ? null : $slice[array_key_last($slice)];
            $next = count($batches) > self::PAGE_SIZE
                ? (is_object($boundary) ? $boundary->id : null)
                : null;

            if ($next === '' || $next === $beforeId) {
                $next = null;
            }

            return [
                'items' => $items,
                'current' => $beforeId,
                'next' => $next,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'items' => [],
                'current' => $beforeId,
                'next' => null,
            ];
        }
    }
}
