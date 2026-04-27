<?php

namespace Laravel\Horizon\Tests\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Jobs\MonitorTag;
use Laravel\Horizon\Jobs\RetryFailedJob;
use Laravel\Horizon\Tests\Feature\Jobs\ConditionallyFailingJob;

class DatabaseRetryJobTest extends DatabaseIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        unset($_SERVER['horizon.fail']);
    }

    protected function tearDown(): void
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
        Queue::push(new ConditionallyFailingJob);
        $this->work();
        $this->assertSame(1, $this->failedJobs());

        $id = DB::table('horizon_jobs')->where('status', 'failed')->value('id');

        dispatch(new MonitorTag('first'));

        unset($_SERVER['horizon.fail']);
        dispatch(new RetryFailedJob($id));

        $retried = json_decode(DB::table('horizon_jobs')->where('id', $id)->value('retried_by'), true);
        $this->assertSame('pending', $retried[0]['status']);

        $this->work();

        $this->assertSame(1, $this->failedJobs());
        $this->assertSame(1, $this->monitoredJobs('first'));

        $retried = json_decode(DB::table('horizon_jobs')->where('id', $id)->value('retried_by'), true);
        $this->assertCount(1, $retried);
        $this->assertNotNull($retried[0]['id']);
        $this->assertNotNull($retried[0]['retried_at']);
        $this->assertSame('completed', $retried[0]['status']);
    }

    public function test_status_is_updated_for_double_failing_jobs()
    {
        $_SERVER['horizon.fail'] = true;
        Queue::push(new ConditionallyFailingJob);
        $this->work();

        $id = DB::table('horizon_jobs')->where('status', 'failed')->value('id');

        dispatch(new RetryFailedJob($id));
        $this->work();

        $retried = json_decode(DB::table('horizon_jobs')->where('id', $id)->value('retried_by'), true);

        $this->assertSame('failed', $retried[0]['status']);
    }
}
