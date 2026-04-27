<?php

namespace Laravel\Horizon\Tests\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Tests\Feature\Jobs\BasicJob;
use Orchestra\Testbench\Attributes\WithConfig;

#[WithConfig('horizon.silenced', [BasicJob::class])]
class DatabaseSilencedJobTest extends DatabaseIntegrationTestCase
{
    public function test_silenced_jobs_are_recorded_with_silenced_status()
    {
        Queue::push(new BasicJob);
        $this->work();

        $this->assertSame('silenced', DB::table('horizon_jobs')->value('status'));
    }

    public function test_silenced_jobs_increment_the_silenced_count()
    {
        Queue::push(new BasicJob);
        $this->work();

        $this->assertSame(1, resolve(JobRepository::class)->countSilenced());
    }

    public function test_silenced_jobs_do_not_count_as_failed()
    {
        Queue::push(new BasicJob);
        $this->work();

        $this->assertSame(0, $this->failedJobs());
    }
}
