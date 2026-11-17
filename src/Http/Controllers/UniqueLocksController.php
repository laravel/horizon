<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\UniqueLocks;

class UniqueLocksController extends Controller
{
    /**
     * The maximum number of pending jobs to inspect for lock owners.
     */
    const PENDING_JOBS_TO_INSPECT = 2000;

    /**
     * Get the unique job locks.
     *
     * @param  \Laravel\Horizon\UniqueLocks  $locks
     * @param  \Laravel\Horizon\Contracts\JobRepository  $jobs
     * @return array
     */
    public function index(UniqueLocks $locks, JobRepository $jobs)
    {
        if (! $locks->supported()) {
            return ['supported' => false, 'locks' => []];
        }

        $owners = $this->pendingJobsByLockOwner($jobs);

        return [
            'supported' => true,
            'locks' => $locks->all()->map(fn ($lock) => $lock + [
                'job_id' => $lock['owner'] ? ($owners[$lock['owner']] ?? null) : null,
            ]),
        ];
    }

    /**
     * Release the given unique job lock.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Laravel\Horizon\UniqueLocks  $locks
     * @return void
     */
    public function destroy(Request $request, UniqueLocks $locks)
    {
        $key = $request->validate([
            'key' => ['required', 'string', 'starts_with:'.UniqueLocks::PREFIX],
        ])['key'];

        $locks->release($key);
    }

    /**
     * Map the unique lock owners of the pending jobs to their job IDs.
     *
     * @param  \Laravel\Horizon\Contracts\JobRepository  $jobs
     * @return array<string, string>
     */
    protected function pendingJobsByLockOwner(JobRepository $jobs)
    {
        $owners = [];

        for ($index = -1; $index < static::PENDING_JOBS_TO_INSPECT - 1; $index += 50) {
            $chunk = $jobs->getPending($index);

            foreach ($chunk as $job) {
                $command = json_decode((string) $job->payload, true)['data']['command'] ?? '';

                $owner = is_string($command)
                    ? Str::match('/uniqueLockOwner";s:\d+:"([^"]+)"/', $command)
                    : '';

                if ($owner !== '') {
                    $owners[$owner] = $job->id;
                }
            }

            if ($chunk->count() < 50) {
                break;
            }
        }

        return $owners;
    }
}
