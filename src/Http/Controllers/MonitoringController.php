<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Jobs\MonitorTag;
use Laravel\Horizon\Jobs\StopMonitoringTag;
use Laravel\Horizon\Repositories\RedisMonitoringMetadataRepository;

class MonitoringController extends Controller
{
    /**
     * The job repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\JobRepository
     */
    public $jobs;

    /**
     * The tag repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\TagRepository
     */
    public $tags;

    /**
     * Create a new controller instance.
     *
     * @param  \Laravel\Horizon\Contracts\JobRepository  $jobs
     * @param  \Laravel\Horizon\Contracts\TagRepository  $tags
     * @return void
     */
    public function __construct(JobRepository $jobs, TagRepository $tags)
    {
        parent::__construct();

        $this->jobs = $jobs;
        $this->tags = $tags;
    }

    /**
     * Get all of the monitored tags and their job counts.
     *
     * @return \Illuminate\Support\Collection
     */
    public function index(RedisMonitoringMetadataRepository $metadata)
    {
        $tags = $this->tags->monitoring();
        $details = $metadata->get($tags);

        return collect($tags)
            ->map(fn ($tag) => [
                'tag' => $tag,
                'count' => $this->tags->count($tag) + $this->tags->count('failed:'.$tag),
                'created_at_human' => $details[$tag]['created_at']
                    ? Carbon::parse($details[$tag]['created_at'])->diffForHumans()
                    : null,
            ] + $details[$tag])
            ->sortBy('tag')
            ->values();
    }

    /**
     * Paginate the jobs for a given tag.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function paginate(Request $request)
    {
        $tag = $request->query('tag');

        $jobIds = $this->tags->paginate(
            $tag, $startingAt = $request->query('starting_at', 0),
            $request->query('limit', 25)
        );

        return [
            'jobs' => $this->getJobs($jobIds, $startingAt),
            'total' => $this->tags->count($tag),
        ];
    }

    /**
     * Get the jobs for the given IDs.
     *
     * @param  array  $jobIds
     * @param  int  $startingAt
     * @return \Illuminate\Support\Collection
     */
    protected function getJobs($jobIds, $startingAt = 0)
    {
        return $this->jobs
            ->getJobs($jobIds, $startingAt)
            ->map(function ($job) {
                $job->payload = json_decode($job->payload);

                return $job;
            })
            ->values();
    }

    /**
     * Start monitoring the given tag.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function store(Request $request)
    {
        abort_unless($request->user(), 403);

        $request->merge([
            'tag' => is_string($request->tag) ? trim($request->tag) : $request->tag,
            'reason' => is_string($request->reason) ? trim($request->reason) : $request->reason,
        ]);
        $data = $request->validate([
            'tag' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();

        dispatch(new MonitorTag($data['tag'], $data['reason'], [
            'id' => $user->getAuthIdentifier(),
            'name' => $user->name ?? (string) $user->getAuthIdentifier(),
        ]));
    }

    /**
     * Update the reason without changing the monitor's creation details.
     */
    public function update(Request $request, $tag, RedisMonitoringMetadataRepository $metadata)
    {
        abort_unless($request->user(), 403);
        abort_unless(in_array($tag, $this->tags->monitoring(), true), 404);

        $request->merge([
            'reason' => is_string($request->reason) ? trim($request->reason) : $request->reason,
        ]);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $metadata->updateReason($tag, $data['reason']);
    }

    /**
     * Stop monitoring the given tag.
     *
     * @param  string  $tag
     * @return void
     */
    public function destroy(Request $request, $tag)
    {
        abort_unless($request->user(), 403);

        dispatch(new StopMonitoringTag($tag));
    }
}
