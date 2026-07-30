<?php

namespace Laravel\Horizon\Tests\Controller;

use Illuminate\Bus\BatchRepository;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Tests\ControllerTest;
use Laravel\Horizon\WaitTimeCalculator;
use Mockery;

class DashboardStatsControllerTest extends ControllerTest
{
    public function test_all_stats_are_correctly_returned()
    {
        // Setup supervisor data...
        $supervisors = Mockery::mock(SupervisorRepository::class);
        $supervisors->shouldReceive('all')->andReturn([
            (object) [
                'processes' => [
                    'redis:first' => 10,
                    'redis:second' => 10,
                ],
            ],
            (object) [
                'processes' => [
                    'redis:first' => 10,
                ],
            ],
        ]);
        $this->app->instance(SupervisorRepository::class, $supervisors);

        // Setup metrics data...
        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('jobsProcessedPerMinute')->andReturn(1);
        $metrics->shouldReceive('queueWithMaximumRuntime')->andReturn('default');
        $metrics->shouldReceive('queueWithMaximumThroughput')->andReturn('default');
        $metrics->shouldReceive('measuredJobs')->andReturn(['App\\Jobs\\One']);
        $metrics->shouldReceive('measuredQueues')->andReturn(['default', 'reports']);
        $this->app->instance(MetricsRepository::class, $metrics);

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countRecentlyFailed')->andReturn(1);
        $jobs->shouldReceive('countRecent')->andReturn(1);
        $jobs->shouldReceive('countPending')->andReturn(4);
        $jobs->shouldReceive('countCompleted')->andReturn(8);
        $jobs->shouldReceive('countSilenced')->andReturn(2);
        $jobs->shouldReceive('countFailed')->andReturn(1);
        $this->app->instance(JobRepository::class, $jobs);

        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitoring')->andReturn(['first', 'second', 'third']);
        $this->app->instance(TagRepository::class, $tags);

        $batches = Mockery::mock(BatchRepository::class);
        $batches->shouldReceive('get')->with(51, null)->andReturn([
            (object) ['id' => 'batch-1'],
        ]);
        $this->app->instance(BatchRepository::class, $batches);

        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $masters->shouldReceive('all')->andReturn([]);
        $this->app->instance(MasterSupervisorRepository::class, $masters);

        // Setup wait time data...
        $wait = Mockery::mock(WaitTimeCalculator::class);
        $wait->shouldReceive('calculate')->andReturn([
            'first' => 20,
            'second' => 10,
        ]);
        $this->app->instance(WaitTimeCalculator::class, $wait);

        $response = $this->actingAs(new Fakes\User)
                    ->get('/horizon/api/stats');

        $response->assertJson([
            'jobsPerMinute' => 1,
            'wait' => ['first' => 20],
            'processes' => 30,
            'status' => 'inactive',
            'failedJobs' => 1,
            'recentJobs' => 1,
            'queueWithMaxRuntime' => 'default',
            'queueWithMaxThroughput' => 'default',
            'periods' => [
                'failedJobs' => 10080,
                'recentJobs' => 60,
            ],
            'navigationCounts' => [
                'monitoring' => 3,
                'metrics' => 3,
                'batches' => 1,
                'pending' => 4,
                'completed' => 8,
                'silenced' => 2,
                'failed' => 1,
            ],
        ]);
    }

    public function test_paused_status_is_reflected_if_all_master_supervisors_are_paused()
    {
        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $masters->shouldReceive('all')->andReturn([
            (object) [
                'status' => 'paused',
            ],
            (object) [
                'status' => 'paused',
            ],
        ]);
        $this->app->instance(MasterSupervisorRepository::class, $masters);

        $response = $this->actingAs(new Fakes\User)
                    ->get('/horizon/api/stats');

        $response->assertJson([
            'status' => 'paused',
        ]);
    }

    public function test_paused_status_isnt_reflected_if_not_all_master_supervisors_are_paused()
    {
        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $masters->shouldReceive('all')->andReturn([
            (object) [
                'status' => 'running',
            ],
            (object) [
                'status' => 'paused',
            ],
        ]);
        $this->app->instance(MasterSupervisorRepository::class, $masters);

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon/api/stats');

        // Legacy public API keeps the running|paused|inactive enum.
        $response->assertJson([
            'status' => 'running',
            'pausedMasters' => 1,
        ]);
    }
}
