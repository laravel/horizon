<?php

namespace Laravel\Horizon\Tests\Controller;

use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Repositories\RedisJobRepository;
use Laravel\Horizon\Repositories\RedisWorkloadRepository;
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

        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $masters->shouldReceive('all')->andReturn([]);
        $this->app->instance(MasterSupervisorRepository::class, $masters);

        // Setup metrics data...
        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('jobsProcessedPerMinute')->andReturn(1);
        $metrics->shouldReceive('throughput')->andReturn(42);
        $metrics->shouldReceive('queueWithMaximumRuntime')->once()->andReturn('default');
        $metrics->shouldReceive('queueWithMaximumThroughput')->once()->andReturn('default');
        $metrics->shouldReceive('runtimeForQueue')->once()->with('default')->andReturn(1500);
        $metrics->shouldReceive('throughputForQueue')->once()->with('default')->andReturn(230);
        $metrics->shouldReceive('measuredJobs')->andReturn(['App\\Jobs\\A', 'App\\Jobs\\B']);
        $metrics->shouldReceive('measuredQueues')->andReturn(['default', 'reports', 'mail']);
        $this->app->instance(MetricsRepository::class, $metrics);

        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitoring')->once()->andReturn(['first', 'second', 'third']);
        $this->app->instance(TagRepository::class, $tags);

        $this->setupBatchTableForStats();

        $jobs = Mockery::mock(RedisJobRepository::class);
        $jobs->shouldReceive('countRecentlyFailed')->andReturn(1);
        $jobs->shouldReceive('countFailedSince')->with(60)->andReturn(2);
        $jobs->shouldReceive('countFailedSince')->with(1440)->andReturn(7);
        $jobs->shouldReceive('countRecent')->andReturn(1);
        $jobs->shouldReceive('countRecentSince')->with(60)->andReturn(11);
        $jobs->shouldReceive('countPending')->andReturn(2);
        $jobs->shouldReceive('countCompleted')->andReturn(30);
        $jobs->shouldReceive('countSilenced')->andReturn(4);
        $jobs->shouldReceive('countFailed')->andReturn(5);
        $this->app->instance(JobRepository::class, $jobs);

        $workload = Mockery::mock(RedisWorkloadRepository::class);
        $workload->shouldReceive('processing')->once()->andReturnTrue();
        $this->app->instance(WorkloadRepository::class, $workload);

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
            'throughput' => 42,
            'wait' => ['first' => 20],
            'processes' => 30,
            'processing' => true,
            'status' => 'inactive',
            'failedJobs' => 1,
            'failedJobsPastHour' => 2,
            'failedJobsPastDay' => 7,
            'recentJobs' => 1,
            'recentJobsPastHour' => 11,
            'queueWithMaxRuntime' => 'default',
            'queueWithMaxThroughput' => 'default',
            'maxRuntime' => 1.5,
            'maxThroughput' => 230,
            'periods' => [
                'failedJobs' => 10080,
                'recentJobs' => 60,
                'completedJobs' => 60,
            ],
            'navigation' => [
                'monitoring' => 3,
                'metrics' => 5,
                'batches' => 0,
                'pending' => 2,
                'completed' => 30,
                'silenced' => 4,
                'failed' => 5,
            ],
        ]);

        $response->assertJsonMissingPath('batches.active');
        $response->assertJsonMissingPath('batches.previews');
    }

    public function test_navigation_batch_count_is_null_when_batch_table_is_missing()
    {
        $this->app['config']->set('queue.batching.database', 'testing');
        $this->app['config']->set('queue.batching.table', 'missing_job_batches');
        $this->app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countRecentlyFailed')->andReturn(0);
        $jobs->shouldReceive('countRecent')->andReturn(0);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitoring')->andReturn([]);
        $this->app->instance(TagRepository::class, $tags);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('jobsProcessedPerMinute')->andReturn(0);
        $metrics->shouldReceive('throughput')->andReturn(0);
        $metrics->shouldReceive('queueWithMaximumRuntime')->once()->andReturn(null);
        $metrics->shouldReceive('queueWithMaximumThroughput')->once()->andReturn(null);
        $metrics->shouldReceive('runtimeForQueue')->never();
        $metrics->shouldReceive('throughputForQueue')->never();
        $metrics->shouldReceive('measuredJobs')->andReturn([]);
        $metrics->shouldReceive('measuredQueues')->andReturn([]);
        $this->app->instance(MetricsRepository::class, $metrics);

        $workload = Mockery::mock(WorkloadRepository::class);
        $workload->shouldReceive('get')->andReturn([]);
        $this->app->instance(WorkloadRepository::class, $workload);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/stats')
            ->assertOk()
            ->assertJsonPath('navigation.batches', null)
            ->assertJsonPath('navigation.monitoring', 0)
            ->assertJsonPath('navigation.metrics', 0)
            ->assertJsonPath('queueWithMaxRuntime', null)
            ->assertJsonPath('queueWithMaxThroughput', null)
            ->assertJsonPath('maxRuntime', null)
            ->assertJsonPath('maxThroughput', null);
    }

    public function test_navigation_batch_count_returns_total_retained_batches()
    {
        $this->setupBatchTableForStats();

        \Illuminate\Support\Facades\DB::connection('testing')->table('job_batches')->insert([
            [
                'id' => 'batch-6',
                'name' => 'Fourth Active',
                'total_jobs' => 12,
                'pending_jobs' => 8,
                'failed_jobs' => 0,
                'failed_job_ids' => '[]',
                'options' => serialize([]),
                'cancelled_at' => null,
                'created_at' => 600,
                'finished_at' => null,
            ],
            [
                'id' => 'batch-5',
                'name' => 'Archive Audit Logs',
                'total_jobs' => 100,
                'pending_jobs' => 40,
                'failed_jobs' => 0,
                'failed_job_ids' => '[]',
                'options' => serialize([]),
                'cancelled_at' => null,
                'created_at' => 500,
                'finished_at' => null,
            ],
            [
                'id' => 'batch-4',
                'name' => 'Send Reports',
                'total_jobs' => 20,
                'pending_jobs' => 10,
                'failed_jobs' => 2,
                'failed_job_ids' => '[]',
                'options' => serialize([]),
                'cancelled_at' => null,
                'created_at' => 400,
                'finished_at' => null,
            ],
            [
                'id' => 'batch-3',
                'name' => '',
                'total_jobs' => 10,
                'pending_jobs' => 10,
                'failed_jobs' => 0,
                'failed_job_ids' => '[]',
                'options' => serialize([]),
                'cancelled_at' => null,
                'created_at' => 300,
                'finished_at' => null,
            ],
            [
                'id' => 'batch-2',
                'name' => 'Failure Stalled',
                'total_jobs' => 10,
                'pending_jobs' => 2,
                'failed_jobs' => 2,
                'failed_job_ids' => '[]',
                'options' => serialize([]),
                'cancelled_at' => null,
                'created_at' => 200,
                'finished_at' => null,
            ],
            [
                'id' => 'batch-1',
                'name' => 'Cancelled',
                'total_jobs' => 10,
                'pending_jobs' => 5,
                'failed_jobs' => 0,
                'failed_job_ids' => '[]',
                'options' => serialize([]),
                'cancelled_at' => 100,
                'created_at' => 100,
                'finished_at' => null,
            ],
            [
                'id' => 'batch-0',
                'name' => 'Finished',
                'total_jobs' => 10,
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'failed_job_ids' => '[]',
                'options' => serialize([]),
                'cancelled_at' => null,
                'created_at' => 50,
                'finished_at' => 75,
            ],
        ]);

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countRecentlyFailed')->andReturn(0);
        $jobs->shouldReceive('countRecent')->andReturn(0);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->stubNavigationDependencies(false);

        $workload = Mockery::mock(WorkloadRepository::class);
        $workload->shouldReceive('get')->andReturn([]);
        $this->app->instance(WorkloadRepository::class, $workload);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/stats')
            ->assertOk()
            ->assertJsonPath('navigation.batches', 7);
    }

    public function test_navigation_batch_count_is_null_for_dynamodb_batching()
    {
        $this->app['config']->set('queue.batching.driver', 'dynamodb');

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countRecentlyFailed')->andReturn(0);
        $jobs->shouldReceive('countRecent')->andReturn(0);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->stubNavigationDependencies(false);

        $workload = Mockery::mock(WorkloadRepository::class);
        $workload->shouldReceive('get')->andReturn([]);
        $this->app->instance(WorkloadRepository::class, $workload);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/stats')
            ->assertOk()
            ->assertJsonPath('navigation.batches', null);
    }

    public function test_failed_jobs_past_hour_is_null_when_repository_lacks_count_failed_since()
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countRecentlyFailed')->andReturn(1);
        $jobs->shouldReceive('countRecent')->andReturn(1);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->stubNavigationDependencies();

        $workload = Mockery::mock(WorkloadRepository::class);
        $workload->shouldReceive('get')->once()->andReturn([]);
        $this->app->instance(WorkloadRepository::class, $workload);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/stats')
            ->assertOk()
            ->assertJsonPath('failedJobsPastHour', null)
            ->assertJsonPath('failedJobsPastDay', null);
    }

    public function test_recent_jobs_past_hour_is_null_when_repository_lacks_count_recent_since()
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countRecentlyFailed')->andReturn(1);
        $jobs->shouldReceive('countRecent')->andReturn(1);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->stubNavigationDependencies();

        $workload = Mockery::mock(WorkloadRepository::class);
        $workload->shouldReceive('get')->once()->andReturn([]);
        $this->app->instance(WorkloadRepository::class, $workload);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/stats')
            ->assertOk()
            ->assertJsonPath('recentJobsPastHour', null);
    }

    public function test_recent_jobs_past_hour_is_null_when_recent_trim_is_below_one_hour()
    {
        $this->app['config']->set('horizon.trim.recent', 30);

        $jobs = Mockery::mock(RedisJobRepository::class);
        $jobs->shouldReceive('countRecentlyFailed')->andReturn(1);
        $jobs->shouldReceive('countFailedSince')->with(60)->andReturn(2);
        $jobs->shouldReceive('countFailedSince')->with(1440)->andReturn(7);
        $jobs->shouldReceive('countRecent')->andReturn(1);
        $jobs->shouldReceive('countRecentSince')->never();
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->stubNavigationDependencies();

        $workload = Mockery::mock(RedisWorkloadRepository::class);
        $workload->shouldReceive('processing')->once()->andReturnFalse();
        $this->app->instance(WorkloadRepository::class, $workload);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/stats')
            ->assertOk()
            ->assertJsonPath('recentJobsPastHour', null)
            ->assertJsonPath('periods.recentJobs', 30);
    }

    public function test_failed_jobs_past_hour_is_null_when_failed_trim_is_below_one_hour()
    {
        $this->app['config']->set('horizon.trim.failed', 30);

        $jobs = Mockery::mock(RedisJobRepository::class);
        $jobs->shouldReceive('countRecentlyFailed')->andReturn(1);
        $jobs->shouldReceive('countFailedSince')->never();
        $jobs->shouldReceive('countRecent')->andReturn(1);
        $jobs->shouldReceive('countRecentSince')->with(60)->andReturn(4);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->stubNavigationDependencies();

        $workload = Mockery::mock(RedisWorkloadRepository::class);
        $workload->shouldReceive('processing')->once()->andReturnFalse();
        $this->app->instance(WorkloadRepository::class, $workload);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/stats')
            ->assertOk()
            ->assertJsonPath('failedJobsPastHour', null)
            ->assertJsonPath('failedJobsPastDay', null);
    }

    public function test_failed_jobs_past_day_is_null_when_failed_trim_is_below_one_day()
    {
        $this->app['config']->set('horizon.trim.failed', 720);

        $jobs = Mockery::mock(RedisJobRepository::class);
        $jobs->shouldReceive('countRecentlyFailed')->andReturn(1);
        $jobs->shouldReceive('countFailedSince')->with(60)->andReturn(2);
        $jobs->shouldReceive('countFailedSince')->with(1440)->never();
        $jobs->shouldReceive('countRecent')->andReturn(1);
        $jobs->shouldReceive('countRecentSince')->with(60)->andReturn(4);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->stubNavigationDependencies();

        $workload = Mockery::mock(RedisWorkloadRepository::class);
        $workload->shouldReceive('processing')->once()->andReturnFalse();
        $this->app->instance(WorkloadRepository::class, $workload);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/stats')
            ->assertOk()
            ->assertJsonPath('failedJobsPastHour', 2)
            ->assertJsonPath('failedJobsPastDay', null);
    }

    public function test_processing_is_derived_from_workload_reserved_counts_when_helper_is_absent()
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countRecentlyFailed')->andReturn(0);
        $jobs->shouldReceive('countRecent')->andReturn(0);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->stubNavigationDependencies();

        $workload = Mockery::mock(WorkloadRepository::class);
        $workload->shouldReceive('get')->once()->andReturn([
            [
                'connection' => 'redis',
                'name' => 'default',
                'length' => 3,
                'reserved' => 2,
                'delayed' => 1,
                'wait' => 0,
                'processes' => 1,
                'split_queues' => null,
            ],
        ]);
        $this->app->instance(WorkloadRepository::class, $workload);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/stats')
            ->assertOk()
            ->assertJsonPath('processing', true);
    }

    public function test_processing_uses_repository_helper_when_available()
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countRecentlyFailed')->andReturn(0);
        $jobs->shouldReceive('countRecent')->andReturn(0);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->stubNavigationDependencies();

        $workload = Mockery::mock(RedisWorkloadRepository::class);
        $workload->shouldReceive('processing')->once()->andReturnFalse();
        $workload->shouldReceive('get')->never();
        $this->app->instance(WorkloadRepository::class, $workload);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/stats')
            ->assertOk()
            ->assertJsonPath('processing', false);
    }

    protected function stubNavigationDependencies($withEmptyBatchTable = true)
    {
        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitoring')->andReturn([]);
        $this->app->instance(TagRepository::class, $tags);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('jobsProcessedPerMinute')->andReturn(0);
        $metrics->shouldReceive('throughput')->andReturn(0);
        $metrics->shouldReceive('queueWithMaximumRuntime')->andReturn(null);
        $metrics->shouldReceive('queueWithMaximumThroughput')->andReturn(null);
        $metrics->shouldReceive('runtimeForQueue')->never();
        $metrics->shouldReceive('throughputForQueue')->never();
        $metrics->shouldReceive('measuredJobs')->andReturn([]);
        $metrics->shouldReceive('measuredQueues')->andReturn([]);
        $this->app->instance(MetricsRepository::class, $metrics);

        if ($withEmptyBatchTable) {
            $this->setupBatchTableForStats();
        }
    }

    protected function setupBatchTableForStats()
    {
        $this->app['config']->set('queue.batching.database', 'testing');
        $this->app['config']->set('queue.batching.table', 'job_batches');
        $this->app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        if (! \Illuminate\Support\Facades\Schema::connection('testing')->hasTable('job_batches')) {
            \Illuminate\Support\Facades\Schema::connection('testing')->create('job_batches', static function ($table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }
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

        $response->assertJson([
            'status' => 'running',
        ]);
    }
}
