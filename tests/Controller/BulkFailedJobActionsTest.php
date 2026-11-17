<?php

namespace Laravel\Horizon\Tests\Controller;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Tests\ControllerTest;
use Laravel\Horizon\Tests\Feature\Jobs\FailingJob;

class BulkFailedJobActionsTest extends ControllerTest
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('queue.failed.driver', 'null');
    }

    public function test_failed_jobs_can_be_retried_in_bulk()
    {
        [$first, $second, $untouched] = $this->failJobs(3);

        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/jobs/retry', ['ids' => [$first, $second]])
            ->assertOk()
            ->assertJson(['retried' => 2]);

        $this->assertSame(2, Queue::size());
        $this->assertNotNull(Redis::connection('horizon')->hget($first, 'retried_by'));
        $this->assertNotNull(Redis::connection('horizon')->hget($second, 'retried_by'));
        $this->assertNull(Redis::connection('horizon')->hget($untouched, 'retried_by'));
    }

    public function test_failed_jobs_can_be_deleted_in_bulk()
    {
        [$first, $second, $untouched] = $this->failJobs(3);

        $this->actingAs(new Fakes\User)
            ->deleteJson('/horizon/api/jobs/failed', ['ids' => [$first, $second, 'unknown']])
            ->assertOk()
            ->assertJson(['deleted' => 2]);

        $repository = app(JobRepository::class);

        $this->assertSame(1, $repository->countFailed());
        $this->assertNull($repository->findFailed($first));
        $this->assertNull($repository->findFailed($second));
        $this->assertNotNull($repository->findFailed($untouched));
    }

    public function test_ids_are_validated()
    {
        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/jobs/retry', ['ids' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids');

        $this->actingAs(new Fakes\User)
            ->deleteJson('/horizon/api/jobs/failed', ['ids' => array_fill(0, 1001, 'id')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids');

        $this->actingAs(new Fakes\User)
            ->deleteJson('/horizon/api/jobs/failed', ['ids' => [['nested']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids.0');
    }

    /**
     * Push and process the given number of failing jobs.
     *
     * @param  int  $count
     * @return array<int, string>
     */
    protected function failJobs($count)
    {
        $ids = collect(range(1, $count))->map(fn () => Queue::push(new FailingJob))->all();

        $this->work($count);

        $this->assertSame($count, $this->failedJobs());

        return $ids;
    }
}
