<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Jobs\MonitorTag;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Jobs\RetryAllFailedJobs;
use Laravel\Horizon\Jobs\RetryFailedJob;
use Laravel\Horizon\Repositories\RedisJobRepository;
use Laravel\Horizon\Tests\IntegrationTest;

class RetryJobTest extends IntegrationTest
{
    public function setUp()
    {
        parent::setUp();

        unset($_SERVER['horizon.fail']);
    }

    public function tearDown()
    {
        parent::tearDown();

        unset($_SERVER['horizon.fail']);
    }

    public function test_nothing_happens_for_failed_job_that_doesnt_exist()
    {
        dispatch(new RetryFailedJob('12345'));
    }

    public function test_failed_job_can_be_retried_successfully_with_a_fresh_id()
    {
        $_SERVER['horizon.fail'] = true;
        $id = Queue::push(new Jobs\ConditionallyFailingJob);
        $this->work();
        $this->assertEquals(1, $this->failedJobs());

        // Monitor the tag so the job is stored in the completed table...
        dispatch(new MonitorTag('first'));

        unset($_SERVER['horizon.fail']);
        dispatch(new RetryFailedJob($id));

        // Test status is set to pending...
        $retried = Redis::connection('horizon')->hget($id, 'retried_by');
        $retried = json_decode($retried, true);
        $this->assertSame('pending', $retried[0]['status']);

        // Work the now-passing job...
        $this->work();

        $this->assertEquals(1, $this->failedJobs());
        $this->assertEquals(1, $this->monitoredJobs('first'));

        // Test that retry job ID reference is stored on original failed job...
        $retried = Redis::connection('horizon')->hget($id, 'retried_by');
        $retried = json_decode($retried, true);
        $this->assertCount(1, $retried);
        $this->assertNotNull($retried[0]['id']);
        $this->assertNotNull($retried[0]['retried_at']);

        // Test status is now completed on the retry...
        $this->assertSame('completed', $retried[0]['status']);
    }

    public function test_status_is_updated_for_double_failing_jobs()
    {
        $_SERVER['horizon.fail'] = true;
        $id = Queue::push(new Jobs\ConditionallyFailingJob);
        $this->work();
        dispatch(new RetryFailedJob($id));
        $this->work();

        // Test that retry job ID reference is stored on original failed job...
        $retried = Redis::connection('horizon')->hget($id, 'retried_by');
        $retried = json_decode($retried, true);

        // Test status is now failed on the retry...
        $this->assertSame('failed', $retried[0]['status']);
    }

    public function test_all_failed_jobs_can_be_retried_with_fresh_ids()
    {
        // Create 2 jobs
        $first = Queue::push(new Jobs\ConditionallyFailingJob(['test', 'first']));
        $second = Queue::push(new Jobs\ConditionallyFailingJob(['test', 'second']));

        // Make them fail
        $_SERVER['horizon.fail'] = true;
        $this->work(2);

        $this->assertEquals(2, $this->failedJobs());

        // Monitor the tags from both jobs
        dispatch(new MonitorTag('test'));
        dispatch(new MonitorTag('first'));
        dispatch(new MonitorTag('second'));

        // Retry both of them
        dispatch(new RetryAllFailedJobs());

        // Test status is set to pending
        $this->assertSame('pending', $this->getJobRetries($first)[0]['status']);
        $this->assertSame('pending', $this->getJobRetries($second)[0]['status']);

        // Work the now-passing jobs
        unset($_SERVER['horizon.fail']);
        $this->work(2);

        // Test failed jobs and monitored tags count
        $this->assertEquals(2, $this->failedJobs());
        $this->assertEquals(2, $this->monitoredJobs('test'));
        $this->assertEquals(1, $this->monitoredJobs('first'));
        $this->assertEquals(1, $this->monitoredJobs('second'));

        // Test if first job has been retried
        $firstRetries = $this->getJobRetries($first);
        $this->assertCount(1, $firstRetries);
        $this->assertNotNull($firstRetries[0]['id']);
        $this->assertNotNull($firstRetries[0]['retried_at']);
        $this->assertSame('completed', $firstRetries[0]['status']);

        // Test if second job has been retried
        $secondRetries = $this->getJobRetries($second);
        $this->assertCount(1, $secondRetries);
        $this->assertNotNull($secondRetries[0]['id']);
        $this->assertNotNull($secondRetries[0]['retried_at']);
        $this->assertSame('completed', $secondRetries[0]['status']);
    }

    public function test_retry_all_failed_jobs_with_pagination()
    {
        // Create 101 jobs (3 pages)
        $jobs = [];
        for ($i = 0; $i <= 101; $i++) {
            $jobs[] = Queue::push(new Jobs\ConditionallyFailingJob);
        }

        // Make all of them fail
        $_SERVER['horizon.fail'] = true;
        $this->work(count($jobs));

        $this->assertEquals(count($jobs), $this->failedJobs());

        // Monitor the job's tag
        dispatch(new MonitorTag('first'));

        // Retry all jobs
        dispatch(new RetryAllFailedJobs());

        // Test if all jobs have been retried
        foreach ($jobs as $id) {
            $this->assertSame('pending', $this->getJobRetries($id)[0]['status']);
        }

        // Work the now-passing jobs
        unset($_SERVER['horizon.fail']);
        $this->work(count($jobs));

        // Test failed jobs and monitored tags count
        $this->assertEquals(count($jobs), $this->failedJobs());
        $this->assertEquals(count($jobs), $this->monitoredJobs('first'));

        // Test if every job has been retried successfully
        foreach ($jobs as $id) {
            $retries = $this->getJobRetries($id);
            $this->assertCount(1, $retries);
            $this->assertNotNull($retries[0]['id']);
            $this->assertNotNull($retries[0]['retried_at']);
            $this->assertSame('completed', $retries[0]['status']);
        }
    }

    public function test_retry_all_does_not_process_jobs_with_non_failed_retries()
    {
        // Create and fail a job
        $first = Queue::push(new Jobs\ConditionallyFailingJob());
        $_SERVER['horizon.fail'] = true;
        $this->work();

        $this->assertEquals(1, $this->failedJobs());

        // Successfully retry the failed job
        dispatch(new RetryFailedJob($first));
        unset($_SERVER['horizon.fail']);
        $this->work();

        // Ensure that the retry has been completed
        $firstRetries = $this->getJobRetries($first);
        $this->assertSame('completed', $firstRetries[0]['status']);
        $this->assertCount(1, $firstRetries);

        // Create another failing job
        $second = Queue::push(new Jobs\ConditionallyFailingJob);
        $_SERVER['horizon.fail'] = true;
        $this->work();

        $this->assertEquals(2, $this->failedJobs());

        // Retry all jobs
        dispatch(new RetryAllFailedJobs());
        unset($_SERVER['horizon.fail']);
        $this->work();

        // The 2nd job should have been retried
        $secondRetries = $this->getJobRetries($second);
        $this->assertSame('completed', $secondRetries[0]['status']);

        // The first job should not have been retried again
        $firstRetries = $this->getJobRetries($first);
        $this->assertCount(1, $firstRetries);
    }

    public function test_retry_all_does_not_process_jobs_which_are_retries_of_another_job()
    {
        // Create a failing job
        $id = Queue::push(new Jobs\FailingJob());
        $this->work();

        $this->assertEquals(1, $this->failedJobs());

        // Retry the failing job
        dispatch(new RetryFailedJob($id));
        $this->work();

        // We should have 2 failed jobs (an original job and its retry)
        $this->assertEquals(2, $this->failedJobs());

        // Retry all failed jobs
        dispatch(new RetryAllFailedJobs());
        $this->work(2);

        // The failed jobs should be tried only once since they are duplicate
        $this->assertEquals(3, $this->failedJobs());
    }

    public function test_failed_jobs_snapshot_gets_deleted_afterwards()
    {
        // Create a failing job
        Queue::push(new Jobs\FailingJob());
        $this->work();

        // Retry all failed jobs
        dispatch(new RetryAllFailedJobs());

        // List of failed jobs snapshots
        $snapshotKeys = collect(Redis::connection('horizon')->keys('*'))
            ->filter(function ($key) {
                $prefix = 'horizon:' . RedisJobRepository::FAILED_JOBS_SNAPSHOT_PREFIX;

                return starts_with($key, $prefix);
            });

        // After RetryAllFailedJobs is done, there should be no remaining snapshots
        $this->assertTrue($snapshotKeys->isEmpty(), 'Failed asserting that jobs snapshots are being deleted.');
    }

    /**
     * @param string $originalId
     * @return array
     */
    private function getJobRetries(string $originalId): array
    {
        $retriedJob = Redis::connection('horizon')->hget($originalId, 'retried_by');
        $retriedJob = json_decode($retriedJob ?? '[]', true);

        return $retriedJob;
    }
}
