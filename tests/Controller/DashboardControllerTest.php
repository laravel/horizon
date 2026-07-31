<?php

namespace Laravel\Horizon\Tests\Controller;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Batch;
use Illuminate\Bus\BatchRepository;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Http\Middleware\HandleInertiaRequests;
use Laravel\Horizon\Repositories\RedisJobRepository;
use Laravel\Horizon\Support\FrameworkCapabilities;
use Laravel\Horizon\Tests\ControllerTest;
use Laravel\Horizon\WaitTimeCalculator;
use Mockery;

class DashboardControllerTest extends ControllerTest
{
    public function test_dashboard_data_is_rendered_as_inertia_props()
    {
        $this->bindDashboardDependencies();

        $capabilities = FrameworkCapabilities::detect();

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard', false)
                ->url('/horizon')
                ->where('horizon.baseUrl', '/horizon')
                ->where('horizon.pollInterval', 5000)
                ->where('horizon.maintenanceMode', false)
                ->where('horizon.processing', true)
                ->where('horizon.capabilities.queuePausing', $capabilities->queuePausing)
                ->where('horizon.capabilities.queuePauseFor', $capabilities->queuePauseFor)
                ->where('navigationCounts.monitoring', 2)
                ->where('navigationCounts.metrics', 3)
                ->where('navigationCounts.batches', 1)
                ->where('navigationCounts.pending', 5)
                ->where('navigationCounts.completed', 36)
                ->where('navigationCounts.silenced', 3)
                ->where('navigationCounts.failed', 6)
                ->where('stats.pendingJobs', 5)
                ->where('stats.pendingReserved', 1)
                ->where('stats.pendingReadyNow', 3)
                ->where('stats.pendingDelayed', 1)
                ->where('stats.failedJobs', 3)
                ->where('stats.failedJobsPastHour', 4)
                ->where('stats.failedJobsPastDay', 6)
                ->where('stats.jobsPerMinute', 12)
                ->where('stats.throughput', 42)
                ->where('stats.recentJobs', 48)
                ->where('stats.hourlyPressure', 48)
                ->where('stats.periods.failedJobs', 10080)
                ->where('stats.periods.recentJobs', 60)
                ->where('stats.periods.completedJobs', 60)
                ->where('stats.activeBatches', 1)
                ->where('stats.batchPreviews.0.id', 'batch-1')
                ->where('stats.batchPreviews.0.name', 'Demo batch')
                ->where('stats.batchPreviews.0.progress', 70)
                ->where('stats.status', 'inactive')
                ->where('stats.wait.redis:default', 20)
                ->where('stats.queueWithMaxRuntime', 'default')
                ->where('stats.queueWithMaxThroughput', 'default')
                ->where('stats.maxRuntime', 1.5)
                ->where('stats.maxThroughput', 9)
                ->where('workload.0.name', 'default')
                ->where('workload.0.connection', 'redis')
                ->where('workload.0.paused', false)
                ->where('workload.0.pausedUntil', null)
                ->where('workload.0.throughput', 9)
                ->where('masters', [])
                ->etc());
    }

    public function test_max_runtime_and_throughput_are_null_when_metric_leaders_are_unavailable()
    {
        $this->bindDashboardDependencies();

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('jobsProcessedPerMinute')->andReturn(12);
        $metrics->shouldReceive('throughput')->andReturn(42);
        $metrics->shouldReceive('throughputForQueue')->with('default')->andReturn(9)->byDefault();
        $metrics->shouldReceive('queueWithMaximumRuntime')->andReturn(null);
        $metrics->shouldReceive('queueWithMaximumThroughput')->andReturn(null);
        $metrics->shouldReceive('measuredJobs')->andReturn(['App\\Jobs\\One', 'App\\Jobs\\Two']);
        $metrics->shouldReceive('measuredQueues')->andReturn(['default']);
        $metrics->shouldNotReceive('runtimeForQueue');
        $this->app->instance(MetricsRepository::class, $metrics);

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard', false)
                ->where('stats.queueWithMaxRuntime', null)
                ->where('stats.queueWithMaxThroughput', null)
                ->where('stats.maxRuntime', null)
                ->where('stats.maxThroughput', null)
                ->etc());
    }

    public function test_max_runtime_is_null_when_runtime_lookup_is_nonnumeric()
    {
        $this->bindDashboardDependencies();

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('jobsProcessedPerMinute')->andReturn(12);
        $metrics->shouldReceive('throughput')->andReturn(42);
        $metrics->shouldReceive('throughputForQueue')->with('default')->andReturn(9)->byDefault();
        $metrics->shouldReceive('queueWithMaximumRuntime')->andReturn('default');
        $metrics->shouldReceive('queueWithMaximumThroughput')->andReturn('default');
        $metrics->shouldReceive('runtimeForQueue')->with('default')->andReturn('n/a');
        $metrics->shouldReceive('measuredJobs')->andReturn(['App\\Jobs\\One', 'App\\Jobs\\Two']);
        $metrics->shouldReceive('measuredQueues')->andReturn(['default']);
        $this->app->instance(MetricsRepository::class, $metrics);

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard', false)
                ->where('stats.queueWithMaxRuntime', 'default')
                ->where('stats.maxRuntime', null)
                ->where('stats.queueWithMaxThroughput', 'default')
                ->where('stats.maxThroughput', 9)
                ->etc());
    }

    public function test_dashboard_partial_reload_can_refresh_stats_without_loading_other_data()
    {
        $this->bindDashboardDependencies();
        $this->app['config']->set('inertia.testing.ensure_pages_exist', false);

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertInertia(function (AssertableInertia $page) {
            $page->component('dashboard', false)
                ->reloadOnly('stats', fn (AssertableInertia $reload) => $reload
                    ->where('stats.jobsPerMinute', 12)
                    ->missing('workload')
                    ->missing('masters')
                    ->etc());
        });
    }

    public function test_dashboard_partial_reload_can_refresh_navigation_counts()
    {
        $this->bindDashboardDependencies();
        $this->app['config']->set('inertia.testing.ensure_pages_exist', false);

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertInertia(function (AssertableInertia $page) {
            $page->component('dashboard', false)
                ->reloadOnly('navigationCounts', fn (AssertableInertia $reload) => $reload
                    ->where('navigationCounts.pending', 5)
                    ->where('navigationCounts.monitoring', 2)
                    ->where('navigationCounts.metrics', 3)
                    ->where('navigationCounts.batches', 1)
                    ->missing('stats')
                    ->missing('workload')
                    ->missing('masters')
                    ->etc());
        });
    }

    public function test_shared_horizon_state_refreshes_processing_activity()
    {
        $this->bindDashboardDependencies();
        $this->app['config']->set('inertia.testing.ensure_pages_exist', false);

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertInertia(function (AssertableInertia $page) {
            $page->component('dashboard', false)
                ->reloadOnly('horizon', fn (AssertableInertia $reload) => $reload
                    ->where('horizon.processing', true)
                    ->missing('navigationCounts')
                    ->missing('stats')
                    ->missing('workload')
                    ->missing('masters')
                    ->etc());
        });
    }

    public function test_shared_horizon_status_is_partially_paused_for_mixed_masters()
    {
        $this->bindDashboardDependencies();

        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $masters->shouldReceive('all')->andReturn([
            (object) ['status' => 'running'],
            (object) ['status' => 'paused'],
        ]);
        $this->app->instance(MasterSupervisorRepository::class, $masters);

        $this->actingAs(new Fakes\User)
            ->get('/horizon')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard', false)
                ->where('horizon.status', 'partially_paused')
                ->where('stats.status', 'partially_paused')
                ->where('stats.pausedMasters', 1)
                ->etc());
    }

    public function test_shared_horizon_status_is_paused_when_every_master_is_paused()
    {
        $this->bindDashboardDependencies();

        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $masters->shouldReceive('all')->andReturn([
            (object) ['status' => 'paused'],
            (object) ['status' => 'paused'],
        ]);
        $this->app->instance(MasterSupervisorRepository::class, $masters);

        $this->actingAs(new Fakes\User)
            ->get('/horizon')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard', false)
                ->where('horizon.status', 'paused')
                ->where('stats.status', 'paused')
                ->where('stats.pausedMasters', 2)
                ->etc());
    }

    public function test_dashboard_uses_the_existing_horizon_authorization()
    {
        $this->bindDashboardDependencies();

        Horizon::auth(fn () => false);

        $this->actingAs(new Fakes\User)
            ->get('/horizon')
            ->assertForbidden();
    }

    public function test_shared_dashboard_props_reflect_application_maintenance_mode()
    {
        $this->app->maintenanceMode()->activate(['time' => time()]);

        try {
            $props = $this->app->make(HandleInertiaRequests::class)
                ->share(Request::create('/horizon'));

            $this->assertTrue($props['horizon']['maintenanceMode']);
        } finally {
            $this->app->maintenanceMode()->deactivate();
        }
    }

    public function test_shared_horizon_capabilities_reflect_unsupported_framework_queue_pausing()
    {
        $this->app->instance(FrameworkCapabilities::class, new FrameworkCapabilities(false, false));

        $props = $this->app->make(HandleInertiaRequests::class)
            ->share(Request::create('/horizon'));

        $this->assertSame([
            'queuePausing' => false,
            'queuePauseFor' => false,
        ], $props['horizon']['capabilities']);
        $this->assertFalse($props['horizon']['capabilities']['queuePausing']);
        $this->assertFalse($props['horizon']['capabilities']['queuePauseFor']);
    }

    public function test_shared_horizon_base_url_combines_proxy_path_and_path_with_slash_normalization()
    {
        $cases = [
            ['proxy' => 'ops', 'path' => 'queues', 'expected' => '/ops/queues'],
            ['proxy' => '/ops/', 'path' => '/queues/', 'expected' => '/ops/queues'],
            ['proxy' => 'admin/proxy', 'path' => 'horizon', 'expected' => '/admin/proxy/horizon'],
            ['proxy' => '', 'path' => 'horizon', 'expected' => '/horizon'],
            ['proxy' => 'ops', 'path' => '', 'expected' => '/ops'],
            ['proxy' => '///ops///', 'path' => '///queues///', 'expected' => '/ops/queues'],
        ];

        foreach ($cases as $case) {
            $this->app['config']->set('horizon.proxy_path', $case['proxy']);
            $this->app['config']->set('horizon.path', $case['path']);

            $props = $this->app->make(HandleInertiaRequests::class)
                ->share(Request::create($case['expected']));

            $this->assertSame(
                $case['expected'],
                $props['horizon']['baseUrl'],
                "Failed for proxy_path={$case['proxy']} path={$case['path']}",
            );
        }
    }

    public function test_failed_period_stats_are_null_when_count_failed_since_is_unavailable()
    {
        $this->bindDashboardDependencies(withFailedSince: false, withRecentSince: false);

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard', false)
                ->where('stats.failedJobsPastHour', null)
                ->where('stats.failedJobsPastDay', null)
                ->where('stats.failedJobs', 3)
                ->etc());
    }

    public function test_failed_period_stats_respect_trim_thresholds()
    {
        $this->app['config']->set('horizon.trim.failed', 30);
        $this->bindDashboardDependencies();

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard', false)
                ->where('stats.failedJobsPastHour', null)
                ->where('stats.failedJobsPastDay', null)
                ->etc());
    }

    public function test_failed_jobs_past_day_is_null_when_trim_is_below_one_day()
    {
        $this->app['config']->set('horizon.trim.failed', 720);
        $this->bindDashboardDependencies();

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard', false)
                ->where('stats.failedJobsPastHour', 4)
                ->where('stats.failedJobsPastDay', null)
                ->etc());
    }

    public function test_completed_jobs_period_falls_back_to_recent_trim()
    {
        $this->bindDashboardDependencies();

        $trim = $this->app['config']->get('horizon.trim', []);
        unset($trim['completed']);
        $trim['recent'] = 120;
        $this->app['config']->set('horizon.trim', $trim);

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard', false)
                ->where('stats.periods.recentJobs', 120)
                ->where('stats.periods.completedJobs', 120)
                ->etc());
    }

    public function test_hourly_pressure_uses_count_recent_when_trim_is_exactly_one_hour()
    {
        $this->app['config']->set('horizon.trim.recent', 60);
        $this->bindDashboardDependencies(withFailedSince: false, withRecentSince: false);

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard', false)
                ->where('stats.hourlyPressure', 48)
                ->where('stats.recentJobs', 48)
                ->etc());
    }

    public function test_hourly_pressure_uses_count_recent_since_when_trim_exceeds_one_hour()
    {
        $this->app['config']->set('horizon.trim.recent', 120);
        $this->bindDashboardDependencies(withRecentSince: true);

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard', false)
                ->where('stats.hourlyPressure', 17)
                ->where('stats.recentJobs', 48)
                ->etc());
    }

    public function test_hourly_pressure_is_null_when_trim_exceeds_one_hour_and_helper_is_unavailable()
    {
        $this->app['config']->set('horizon.trim.recent', 120);
        $this->bindDashboardDependencies(withFailedSince: false, withRecentSince: false);

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard', false)
                ->where('stats.hourlyPressure', null)
                ->where('stats.recentJobs', 48)
                ->etc());
    }

    public function test_hourly_pressure_is_null_when_trim_is_below_one_hour()
    {
        $this->app['config']->set('horizon.trim.recent', 30);
        $this->bindDashboardDependencies();

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard', false)
                ->where('stats.hourlyPressure', null)
                ->where('stats.recentJobs', 48)
                ->etc());
    }

    private function bindDashboardDependencies(bool $withFailedSince = true, bool $withRecentSince = true)
    {
        // Use the real framework capability contract. Forcing queue pausing on
        // unsupported Laravel versions would make QueuePauseStatus call missing
        // QueueManager APIs (or hit Horizon RedisQueue via __call).
        $this->app->instance(FrameworkCapabilities::class, FrameworkCapabilities::detect());

        if ($withFailedSince || $withRecentSince) {
            $jobs = Mockery::mock(RedisJobRepository::class);

            if ($withFailedSince) {
                $jobs->shouldReceive('countFailedSince')->with(60)->andReturn(4)->byDefault();
                $jobs->shouldReceive('countFailedSince')->with(1440)->andReturn(6)->byDefault();
            }

            if ($withRecentSince) {
                $jobs->shouldReceive('countRecentSince')->with(60)->andReturn(17)->byDefault();
            }
        } else {
            $jobs = Mockery::mock(JobRepository::class);
        }

        $jobs->shouldReceive('countRecentlyFailed')->andReturn(3);
        $jobs->shouldReceive('countRecent')->andReturn(48);
        $jobs->shouldReceive('countPending')->andReturn(5);
        $jobs->shouldReceive('countCompleted')->andReturn(36);
        $jobs->shouldReceive('countSilenced')->andReturn(3);
        $jobs->shouldReceive('countFailed')->andReturn(6);
        $this->app->instance(JobRepository::class, $jobs);

        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $masters->shouldReceive('all')->andReturn([]);
        $this->app->instance(MasterSupervisorRepository::class, $masters);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('jobsProcessedPerMinute')->andReturn(12);
        $metrics->shouldReceive('throughput')->andReturn(42);
        $metrics->shouldReceive('throughputForQueue')->with('default')->andReturn(9)->byDefault();
        $metrics->shouldReceive('queueWithMaximumRuntime')->andReturn('default');
        $metrics->shouldReceive('queueWithMaximumThroughput')->andReturn('default');
        $metrics->shouldReceive('runtimeForQueue')->with('default')->andReturn(1500)->byDefault();
        $metrics->shouldReceive('measuredJobs')->andReturn(['App\\Jobs\\One', 'App\\Jobs\\Two']);
        $metrics->shouldReceive('measuredQueues')->andReturn(['default']);
        $this->app->instance(MetricsRepository::class, $metrics);

        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitoring')->andReturn(['first', 'second']);
        $this->app->instance(TagRepository::class, $tags);

        $supervisors = Mockery::mock(SupervisorRepository::class);
        $supervisors->shouldReceive('all')->andReturn([]);
        $this->app->instance(SupervisorRepository::class, $supervisors);

        $wait = Mockery::mock(WaitTimeCalculator::class);
        $wait->shouldReceive('calculate')->andReturn([
            'redis:default' => 20,
        ]);
        $this->app->instance(WaitTimeCalculator::class, $wait);

        $workload = Mockery::mock(WorkloadRepository::class);
        $workload->shouldReceive('get')->andReturn([
            [
                'name' => 'default',
                'connection' => 'redis',
                'length' => 5,
                'processes' => 2,
                'wait' => 20,
            ],
        ]);
        $this->app->instance(WorkloadRepository::class, $workload);

        $queue = new class
        {
            public function pendingState(string $queue): array
            {
                return $queue === 'default'
                    ? ['ready' => 3, 'reserved' => 1, 'delayed' => 1]
                    : ['ready' => 0, 'reserved' => 0, 'delayed' => 0];
            }
        };
        $queues = Mockery::mock(QueueFactory::class);
        $queues->shouldReceive('connection')->with('redis')->andReturn($queue);
        $this->app->instance(QueueFactory::class, $queues);

        $batches = Mockery::mock(BatchRepository::class);
        $batch = new Batch(
            $queues,
            $batches,
            'batch-1',
            'Demo batch',
            10,
            3,
            0,
            [],
            [],
            CarbonImmutable::now(),
        );
        $batches->shouldReceive('get')->with(51, null)->andReturn([$batch]);
        $this->app->instance(BatchRepository::class, $batches);
    }
}
