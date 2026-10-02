<?php

namespace Laravel\Horizon\Tests\Diagnostics;

use Laravel\Doctor\Facades\Doctor;
use Laravel\Horizon\Diagnostics\HorizonProcessesDefaultRedisQueue;

class HorizonProcessesDefaultRedisQueueTest extends DiagnosticTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.env' => 'production']);

        $this->fakeMasters([]);
    }

    public function test_diagnostic_is_registered_with_doctor()
    {
        $this->assertContains(HorizonProcessesDefaultRedisQueue::class, Doctor::registered());
    }

    public function test_diagnostic_skips_without_a_default_queue_connection()
    {
        config(['queue.default' => null]);

        $result = (new HorizonProcessesDefaultRedisQueue)->check();

        $this->assertSame('skip', $result->status->value);
    }

    public function test_diagnostic_skips_when_the_default_queue_is_not_redis_backed()
    {
        config(['queue.default' => 'sync']);

        $result = (new HorizonProcessesDefaultRedisQueue)->check();

        $this->assertSame('skip', $result->status->value);
        $this->assertStringContainsString('not Redis-backed', $result->summary);
    }

    public function test_diagnostic_passes_when_a_runnable_supervisor_watches_the_default_queue()
    {
        $this->configureDefaultQueue('emails');
        $this->configureSupervisors([
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default', 'emails'],
                'maxProcesses' => 1,
            ],
        ]);

        $result = (new HorizonProcessesDefaultRedisQueue)->check();

        $this->assertSame('pass', $result->status->value);
        $this->assertSame('Horizon processes the default Redis queue [redis:emails].', $result->summary);
    }

    public function test_diagnostic_fails_in_production_when_the_default_queue_is_not_watched()
    {
        $this->configureDefaultQueue('emails');
        $this->configureSupervisors([
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default', 'notifications'],
                'maxProcesses' => 1,
            ],
        ]);

        $result = (new HorizonProcessesDefaultRedisQueue)->check();

        $this->assertSame('fail', $result->status->value);
        $this->assertSame('Horizon does not process the default Redis queue [redis:emails].', $result->summary);
        $this->assertSame("- Default: redis:emails\n- Watched: redis:default, redis:notifications", $result->details);
    }

    public function test_diagnostic_ignores_supervisors_that_cannot_start_workers()
    {
        $this->configureDefaultQueue('emails');
        $this->configureSupervisors([
            'disabled' => [
                'connection' => 'redis',
                'queue' => ['emails'],
                'maxProcesses' => 0,
            ],
        ]);

        $result = (new HorizonProcessesDefaultRedisQueue)->check();

        $this->assertSame('fail', $result->status->value);
        $this->assertStringContainsString('Watched: none', $result->details);
    }

    public function test_diagnostic_warns_outside_production_when_the_default_queue_is_not_watched()
    {
        config(['doctor.environments' => ['local' => ['testing']]]);

        $this->configureDefaultQueue('emails');
        $this->configureSupervisors([
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default'],
                'maxProcesses' => 1,
            ],
        ]);

        $result = (new HorizonProcessesDefaultRedisQueue)->check();

        $this->assertSame('warn', $result->status->value);
    }

    public function test_diagnostic_uses_the_environment_recorded_by_a_running_master()
    {
        $this->configureDefaultQueue('emails');
        config(['horizon.environments' => [
            'production' => ['supervisor-1' => ['queue' => ['default']]],
            'workers' => ['supervisor-1' => ['queue' => ['emails']]],
        ]]);
        $this->fakeMasters([
            (object) ['environment' => 'workers'],
        ]);

        $result = (new HorizonProcessesDefaultRedisQueue)->check();

        $this->assertSame('pass', $result->status->value);
    }

    /**
     * Configure the application's default Redis queue.
     */
    private function configureDefaultQueue(string $queue): void
    {
        config([
            'queue.default' => 'redis',
            'queue.connections.redis.driver' => 'redis',
            'queue.connections.redis.queue' => $queue,
        ]);
    }

    /**
     * Configure supervisors for the production environment.
     *
     * @param  array<string, array<string, mixed>>  $supervisors
     */
    private function configureSupervisors(array $supervisors): void
    {
        config([
            'horizon.defaults' => [],
            'horizon.environments' => ['production' => $supervisors],
        ]);
    }
}
