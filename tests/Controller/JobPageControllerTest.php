<?php

namespace Laravel\Horizon\Tests\Controller;

use Inertia\Testing\AssertableInertia;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Tests\ControllerTest;
use Mockery;

class JobPageControllerTest extends ControllerTest
{
    public function test_each_upstream_job_state_has_its_own_inertia_page()
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('getPending')->with(-1)->andReturn(collect());
        $jobs->shouldReceive('countPending')->andReturn(1);
        $jobs->shouldReceive('getCompleted')->with(-1)->andReturn(collect());
        $jobs->shouldReceive('countCompleted')->andReturn(2);
        $jobs->shouldReceive('getFailed')->with(-1)->andReturn(collect());
        $jobs->shouldReceive('countFailed')->andReturn(3);
        $jobs->shouldReceive('getSilenced')->with(-1)->andReturn(collect());
        $jobs->shouldReceive('countSilenced')->andReturn(4);
        $this->app->instance(JobRepository::class, $jobs);

        foreach ([
            '/horizon/jobs/pending' => ['jobs/pending', 1],
            '/horizon/jobs/completed' => ['jobs/completed', 2],
            '/horizon/jobs/failed' => ['jobs/failed', 3],
            '/horizon/jobs/silenced' => ['jobs/silenced', 4],
        ] as $url => [$component, $total]) {
            $this->actingAs(new Fakes\User)
                ->get($url)
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component($component, false)
                    ->where('total', $total)
                    ->where('jobs.data', [])
                    ->etc());
        }
    }

    public function test_failed_page_filters_jobs_by_the_original_tag_search()
    {
        $job = (object) [
            'id' => 'failed-job-id',
            'payload' => json_encode(['displayName' => 'Failing tagged job']),
        ];

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('getJobs')
            ->once()
            ->with(['failed-job-id'], 0)
            ->andReturn(collect([$job]));
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(1);
        $this->app->instance(JobRepository::class, $jobs);

        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitoring')->andReturn([]);
        $tags->shouldReceive('paginate')
            ->once()
            ->with('failed:customer:42', 0, 50)
            ->andReturn(['failed-job-id']);
        $tags->shouldReceive('count')
            ->with('failed:customer:42')
            ->andReturn(1);
        $this->app->instance(TagRepository::class, $tags);

        $this->actingAs(new Fakes\User)
            ->get('/horizon/jobs/failed?tag=customer%3A42')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('jobs/failed', false)
                ->where('tag', 'customer:42')
                ->where('total', 1)
                ->where('jobs.data.0.id', 'failed-job-id')
                ->where('jobs.data.0.payload.displayName', 'Failing tagged job')
                ->etc());
    }

    public function test_job_pages_expose_a_revision_for_detecting_new_entries()
    {
        $job = (object) [
            'id' => 'newest-failed-job',
            'payload' => json_encode(['displayName' => 'Newest failed job']),
        ];

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('getFailed')->once()->with(-1)->andReturn(collect([$job]));
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(13);
        $this->app->instance(JobRepository::class, $jobs);

        $this->actingAs(new Fakes\User)
            ->get('/horizon/jobs/failed')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('jobs/failed', false)
                ->where('listRevision', '[13,"newest-failed-job"]')
                ->etc());
    }

    public function test_job_pages_expose_subsequent_retained_pages_to_inertia_scroll()
    {
        $job = (object) [
            'id' => 'pending-job-51',
            'payload' => json_encode(['displayName' => 'Pending job 51']),
        ];

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('getPending')->once()->with(-1)->andReturn(collect());
        $jobs->shouldReceive('getPending')->once()->with(49)->andReturn(collect([$job]));
        $jobs->shouldReceive('countPending')->andReturn(51);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/jobs/pending', [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => Horizon::inertiaVersion(),
                'X-Inertia-Partial-Component' => 'jobs/pending',
                'X-Inertia-Partial-Data' => 'jobs',
            ])
            ->assertOk()
            ->assertJsonPath('scrollProps.jobs.nextPage', 49)
            ->assertJsonPath('scrollProps.jobs.currentPage', -1);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/jobs/pending?starting_at=49', [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => Horizon::inertiaVersion(),
                'X-Inertia-Partial-Component' => 'jobs/pending',
                'X-Inertia-Partial-Data' => 'jobs',
            ])
            ->assertOk()
            ->assertJsonPath('props.jobs.data.0.id', 'pending-job-51')
            ->assertJsonPath('props.jobs.data.0.payload.displayName', 'Pending job 51')
            ->assertJsonPath('scrollProps.jobs.pageName', 'starting_at')
            ->assertJsonPath('scrollProps.jobs.previousPage', null)
            ->assertJsonPath('scrollProps.jobs.nextPage', null)
            ->assertJsonPath('scrollProps.jobs.currentPage', 49)
            ->assertJsonPath('mergeProps.0', 'jobs.data')
            ->assertJsonPath('matchPropsOn.0', 'jobs.data.id');
    }

    public function test_job_pages_stop_scrolling_at_an_exact_page_boundary()
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('getPending')->once()->with(-1)->andReturn(collect());
        $jobs->shouldReceive('countPending')->andReturn(50);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/jobs/pending', [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => Horizon::inertiaVersion(),
                'X-Inertia-Partial-Component' => 'jobs/pending',
                'X-Inertia-Partial-Data' => 'jobs',
            ])
            ->assertOk()
            ->assertJsonPath('scrollProps.jobs.nextPage', null)
            ->assertJsonPath('scrollProps.jobs.currentPage', -1);
    }

    public function test_retained_jobs_have_an_inertia_detail_page()
    {
        $job = (object) [
            'id' => 'silenced-job-id',
            'connection' => 'redis',
            'queue' => 'default',
            'name' => 'App\\Jobs\\DemoSilencedJob',
            'status' => 'completed',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\DemoSilencedJob',
                'pushedAt' => 1_785_242_394.5968,
                'attempts' => 1,
                'tags' => ['maintenance', 'tenant:42'],
                'data' => [
                    'command' => serialize(['label' => 'Maintenance heartbeat']),
                    'commandName' => 'App\\Jobs\\DemoSilencedJob',
                    'batchId' => null,
                ],
            ]),
            'reserved_at' => '1785242399.5816',
            'completed_at' => '1785242399.7865',
            'failed_at' => null,
            'delay' => null,
            'exception' => null,
            'context' => null,
            'retried_by' => null,
        ];

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('getJobs')
            ->once()
            ->with(['silenced-job-id'])
            ->andReturn(collect([$job]));
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(1);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->actingAs(new Fakes\User)
            ->get('/horizon/jobs/silenced/silenced-job-id')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('jobs/show', false)
                ->where('type', 'silenced')
                ->where('job.id', 'silenced-job-id')
                ->where('job.name', 'App\\Jobs\\DemoSilencedJob')
                ->where('job.tags', ['maintenance', 'tenant:42'])
                ->where('job.attempts', 1)
                ->where('job.pushedAt', 1_785_242_394.5968)
                ->where('job.runtime', 0.2049)
                ->where('job.payload.data.commandName', 'App\\Jobs\\DemoSilencedJob')
                ->where('job.payload.data.decodedCommand.label', 'Maintenance heartbeat')
                ->missing('job.payload.data.command')
                ->etc());
    }

    public function test_failed_jobs_have_an_inertia_detail_page()
    {
        $job = (object) [
            'id' => 'failed-job-id',
            'connection' => 'redis',
            'queue' => 'default',
            'name' => 'App\\Jobs\\DemoFailingJob',
            'status' => 'failed',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\DemoFailingJob',
                'pushedAt' => 1_785_242_396.2982,
                'attempts' => 2,
                'tags' => ['customer:42'],
                'data' => [],
            ]),
            'reserved_at' => '1785242396.3000',
            'completed_at' => null,
            'failed_at' => '1785242396.5500',
            'delay' => null,
            'exception' => "RuntimeException: Intentional failure\nStack trace:",
            'context' => json_encode(['customer' => 42]),
            'retried_by' => json_encode([
                ['id' => 'retry-job-id', 'retried_at' => 1_785_242_400],
            ]),
        ];

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('findFailed')
            ->once()
            ->with('failed-job-id')
            ->andReturn($job);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(1);
        $this->app->instance(JobRepository::class, $jobs);

        $this->actingAs(new Fakes\User)
            ->get('/horizon/jobs/failed/failed-job-id')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('jobs/show', false)
                ->where('type', 'failed')
                ->where('job.id', 'failed-job-id')
                ->where('job.status', 'failed')
                ->where('job.exception', "RuntimeException: Intentional failure\nStack trace:")
                ->where('job.context.customer', 42)
                ->where('job.retriedBy.0.id', 'retry-job-id')
                ->etc());
    }

    public function test_job_detail_page_renders_empty_state_for_unknown_jobs()
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('getJobs')
            ->once()
            ->with(['missing-completed-job'])
            ->andReturn(collect());
        $jobs->shouldReceive('findFailed')
            ->once()
            ->with('missing-failed-job')
            ->andReturn(null);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        foreach ([
            '/horizon/jobs/completed/missing-completed-job' => 'completed',
            '/horizon/jobs/failed/missing-failed-job' => 'failed',
        ] as $url => $type) {
            $this->actingAs(new Fakes\User)
                ->get($url)
                ->assertNotFound()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('jobs/show', false)
                    ->where('type', $type)
                    ->where('job', null)
                    ->etc());
        }
    }

    public function test_job_detail_page_returns_ok_for_missing_jobs_on_inertia_visits()
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('getJobs')
            ->once()
            ->with(['missing-pending-job'])
            ->andReturn(collect());
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/jobs/pending/missing-pending-job', [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => Horizon::inertiaVersion(),
            ])
            ->assertOk()
            ->assertJsonPath('component', 'jobs/show')
            ->assertJsonPath('props.type', 'pending')
            ->assertJsonPath('props.job', null);
    }
}
