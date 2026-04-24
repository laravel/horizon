<?php

namespace Laravel\Horizon\Console;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Laravel\Horizon\Enums\JobReferenceType;
use Laravel\Horizon\Enums\JobStatus;
use Laravel\Horizon\Exceptions\JobLostException;
use Laravel\Horizon\Models\HorizonJob;
use Laravel\Horizon\Models\HorizonJobReference;

class RecoverStaleJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:recover-stale {--chunk=1000 : Number of rows processed per DB round-trip}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fail Horizon jobs stuck in the Reserved status past the queue retry window.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $recovered = 0;

        foreach ($this->connectionRetryAfterSeconds() as $connection => $retryAfter) {
            $cutoff = CarbonImmutable::now()->subSeconds($retryAfter);

            HorizonJob::where('status', JobStatus::Reserved)
                ->where('connection', $connection)
                ->where('reserved_at', '<', $cutoff)
                ->orderBy('id')
                ->chunkById($chunk, function ($jobs) use ($connection, &$recovered) {
                    foreach ($jobs as $job) {
                        $this->failStaleJob($job, $connection);
                        $recovered++;
                    }
                });
        }

        $this->components->info("Recovered {$recovered} stale reserved jobs.");

        return self::SUCCESS;
    }

    /**
     * Get the retry_after configuration for each configured queue connection.
     *
     * @return array<string, int>
     */
    protected function connectionRetryAfterSeconds(): array
    {
        $connections = [];

        foreach ((array) config('queue.connections', []) as $name => $options) {
            if (! is_array($options)) {
                continue;
            }

            $connections[$name] = (int) ($options['retry_after'] ?? 90);
        }

        return $connections;
    }

    /**
     * Transition the given stale Reserved job into Failed with a synthetic exception.
     *
     * @param  \Laravel\Horizon\Models\HorizonJob  $job
     * @param  string  $connection
     * @return void
     */
    protected function failStaleJob(HorizonJob $job, string $connection): void
    {
        $exception = JobLostException::forJob((string) $job->id, $connection);

        $job->fill([
            'status' => JobStatus::Failed,
            'exception' => (string) $exception,
            'failed_at' => CarbonImmutable::now(),
            'expires_at' => CarbonImmutable::now()->addMinutes((int) config('horizon.trim.failed', 10080)),
        ])->save();

        $score = CarbonImmutable::now()->getTimestamp() * 1_000_000;

        foreach ([JobReferenceType::Failed, JobReferenceType::RecentFailed] as $type) {
            HorizonJobReference::updateOrCreate(
                ['type' => $type, 'job_id' => $job->id],
                ['queue' => $job->queue, 'score' => $score]
            );
        }

        HorizonJobReference::where('type', JobReferenceType::Pending)
            ->where('job_id', $job->id)
            ->delete();
    }
}
