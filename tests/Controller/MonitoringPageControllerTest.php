<?php

namespace Laravel\Horizon\Tests\Controller;

use Inertia\Testing\AssertableInertia;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Tests\ControllerTest;
use Mockery;

class MonitoringPageControllerTest extends ControllerTest
{
    public function test_monitoring_page_lists_monitored_tags()
    {
        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitoring')->andReturn(['beta', 'alpha']);
        $tags->shouldReceive('count')->with('beta')->andReturn(2);
        $tags->shouldReceive('count')->with('failed:beta')->andReturn(1);
        $tags->shouldReceive('count')->with('alpha')->andReturn(0);
        $tags->shouldReceive('count')->with('failed:alpha')->andReturn(0);
        $this->app->instance(TagRepository::class, $tags);

        $this->actingAs(new Fakes\User)
            ->get('/horizon/monitoring')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('monitoring', false)
                ->where('tags.0.tag', 'alpha')
                ->where('tags.0.count', 0)
                ->where('tags.1.tag', 'beta')
                ->where('tags.1.count', 3)
                ->etc());
    }

    public function test_monitoring_tag_jobs_page_uses_the_tag_storage_key()
    {
        $job = (object) [
            'id' => 'monitored-job-1',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\Example']),
        ];

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $jobs->shouldReceive('getJobs')
            ->once()
            ->with(['monitored-job-1'], 0)
            ->andReturn(collect([$job]));
        $this->app->instance(JobRepository::class, $jobs);

        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitoring')->andReturn([]);
        $tags->shouldReceive('paginate')
            ->once()
            ->with('customer:42', 0, 50)
            ->andReturn(['monitored-job-1']);
        $tags->shouldReceive('count')
            ->with('customer:42')
            ->andReturn(1);
        $this->app->instance(TagRepository::class, $tags);

        $this->actingAs(new Fakes\User)
            ->get('/horizon/monitoring/'.rawurlencode('customer:42').'/jobs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('monitoring/tag-jobs', false)
                ->where('tag', 'customer:42')
                ->where('failed', false)
                ->where('total', 1)
                ->where('jobs.data.0.id', 'monitored-job-1')
                ->etc());
    }

    public function test_monitoring_tag_failed_page_uses_the_failed_tag_storage_key()
    {
        $job = (object) [
            'id' => 'failed-monitored-job-1',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\Failing']),
        ];

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $jobs->shouldReceive('getJobs')
            ->once()
            ->with(['failed-monitored-job-1'], 0)
            ->andReturn(collect([$job]));
        $this->app->instance(JobRepository::class, $jobs);

        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitoring')->andReturn([]);
        $tags->shouldReceive('paginate')
            ->once()
            ->with('failed:reports', 0, 50)
            ->andReturn(['failed-monitored-job-1']);
        $tags->shouldReceive('count')
            ->with('failed:reports')
            ->andReturn(1);
        $this->app->instance(TagRepository::class, $tags);

        $this->actingAs(new Fakes\User)
            ->get('/horizon/monitoring/reports/failed')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('monitoring/tag-jobs', false)
                ->where('tag', 'reports')
                ->where('failed', true)
                ->where('total', 1)
                ->where('jobs.data.0.id', 'failed-monitored-job-1')
                ->etc());
    }

    public function test_monitoring_tag_jobs_expose_scroll_metadata()
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('getJobs')->andReturn(collect());
        $this->app->instance(JobRepository::class, $jobs);

        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('paginate')->with('orders', 0, 50)->andReturn([]);
        $tags->shouldReceive('count')->with('orders')->andReturn(0);
        $this->app->instance(TagRepository::class, $tags);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/monitoring/orders/jobs', [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => Horizon::inertiaVersion(),
                'X-Inertia-Partial-Component' => 'monitoring/tag-jobs',
                'X-Inertia-Partial-Data' => 'jobs',
            ])
            ->assertOk()
            ->assertJsonPath('scrollProps.jobs.pageName', 'starting_at')
            ->assertJsonPath('matchPropsOn.0', 'jobs.data.id');
    }
}
