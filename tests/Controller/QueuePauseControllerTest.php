<?php

namespace Laravel\Horizon\Tests\Controller;

use Carbon\CarbonImmutable;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Support\FrameworkCapabilities;
use Laravel\Horizon\Tests\ControllerTest;

class QueuePauseControllerTest extends ControllerTest
{
    public function test_queue_pause_and_resume_are_forbidden_when_horizon_auth_denies_access()
    {
        $capabilities = $this->requireQueuePausingSupport();
        $this->app->instance(FrameworkCapabilities::class, $capabilities);

        $queues = app(QueueManager::class);

        $this->assertFalse($queues->isPaused('redis', 'reports'));

        Horizon::auth(fn () => false);

        $this->actingAs(new Fakes\User)
            ->post('/horizon/api/queues/redis/reports/pause', ['duration_minutes' => 15])
            ->assertForbidden();

        $this->assertFalse($queues->isPaused('redis', 'reports'));

        $this->actingAs(new Fakes\User)
            ->delete('/horizon/api/queues/redis/reports/pause')
            ->assertForbidden();

        $this->assertFalse($queues->isPaused('redis', 'reports'));
    }

    public function test_unsupported_queue_pausing_returns_not_found_before_validation()
    {
        $this->app->instance(FrameworkCapabilities::class, new FrameworkCapabilities(false));

        $this->actingAs(new Fakes\User)
            ->post('/horizon/api/queues/unsupported/reports/pause', ['duration_minutes' => 0])
            ->assertNotFound();
    }

    public function test_supported_queue_pausing_can_pause_and_resume_a_queue()
    {
        $this->requireQueuePausingSupport();
        $this->app->instance(FrameworkCapabilities::class, new FrameworkCapabilities(
            queuePausing: true,
            queuePauseFor: false,
        ));

        $this->actingAs(new Fakes\User)
            ->post('/horizon/api/queues/redis/reports/pause')
            ->assertNoContent();

        $this->assertTrue(app(QueueManager::class)->isPaused('redis', 'reports'));

        $this->actingAs(new Fakes\User)
            ->delete('/horizon/api/queues/redis/reports/pause')
            ->assertNoContent();

        $this->assertFalse(app(QueueManager::class)->isPaused('redis', 'reports'));
    }

    public function test_timed_queue_pausing_uses_framework_pause_for_without_horizon_deadline_storage()
    {
        $this->requireTimedQueuePausingSupport();

        CarbonImmutable::setTestNow('2026-07-28 12:00:00');
        $this->app->instance(FrameworkCapabilities::class, new FrameworkCapabilities(true, true));

        try {
            $this->actingAs(new Fakes\User)
                ->post('/horizon/api/queues/redis/reports/pause', [
                    'duration_minutes' => 15,
                ])
                ->assertNoContent();

            $this->assertTrue(app(QueueManager::class)->isPaused('redis', 'reports'));
            $this->assertNull(
                cache()->store()->get('horizon:queue-pause:'.hash('sha256', "redis\0reports")),
            );

            $this->actingAs(new Fakes\User)
                ->delete('/horizon/api/queues/redis/reports/pause')
                ->assertNoContent();

            $this->assertFalse(app(QueueManager::class)->isPaused('redis', 'reports'));
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_indefinite_queue_pausing_uses_framework_pause()
    {
        $this->requireTimedQueuePausingSupport();
        $this->app->instance(FrameworkCapabilities::class, new FrameworkCapabilities(true, true));

        $this->actingAs(new Fakes\User)
            ->post('/horizon/api/queues/redis/reports/pause')
            ->assertNoContent();

        $this->assertTrue(app(QueueManager::class)->isPaused('redis', 'reports'));
        $this->assertNull(
            cache()->store()->get('horizon:queue-pause:'.hash('sha256', "redis\0reports")),
        );
    }

    public function test_duration_falls_back_to_indefinite_pause_without_timed_capability()
    {
        $this->requireQueuePausingSupport();

        CarbonImmutable::setTestNow('2026-07-28 12:00:00');
        $this->app->instance(FrameworkCapabilities::class, new FrameworkCapabilities(true, false));

        try {
            $this->actingAs(new Fakes\User)
                ->post('/horizon/api/queues/redis/reports/pause', [
                    'duration_minutes' => 15,
                ])
                ->assertNoContent();

            $this->assertTrue(app(QueueManager::class)->isPaused('redis', 'reports'));
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_resume_works_with_basic_queue_pausing_only()
    {
        $this->requireQueuePausingSupport();
        $this->app->instance(FrameworkCapabilities::class, new FrameworkCapabilities(true, false));

        app(QueueManager::class)->pause('redis', 'reports');

        $this->actingAs(new Fakes\User)
            ->delete('/horizon/api/queues/redis/reports/pause')
            ->assertNoContent();

        $this->assertFalse(app(QueueManager::class)->isPaused('redis', 'reports'));
    }

    public function test_detect_reports_separate_basic_and_timed_capabilities()
    {
        if (! $this->frameworkExposesQueuePausingApis()) {
            $capabilities = FrameworkCapabilities::detect();

            $this->assertFalse($capabilities->queuePausing);
            $this->assertFalse($capabilities->queuePauseFor);
            $this->assertSame([
                'queuePausing' => false,
                'queuePauseFor' => false,
            ], $capabilities->toArray());

            return;
        }

        if (property_exists(Worker::class, 'pausable')) {
            $original = Worker::$pausable;
            Worker::$pausable = true;
        }

        try {
            $capabilities = FrameworkCapabilities::detect();

            $this->assertTrue(method_exists(QueueManager::class, 'pause'));
            $this->assertTrue(method_exists(QueueManager::class, 'resume'));
            $this->assertTrue(method_exists(QueueManager::class, 'isPaused'));
            $this->assertTrue($capabilities->queuePausing);
            $this->assertSame(
                method_exists(QueueManager::class, 'pauseFor'),
                $capabilities->queuePauseFor,
            );
            $this->assertSame([
                'queuePausing' => $capabilities->queuePausing,
                'queuePauseFor' => $capabilities->queuePauseFor,
            ], $capabilities->toArray());
        } finally {
            if (isset($original)) {
                Worker::$pausable = $original;
            }
        }
    }

    public function test_detect_disables_queue_pausing_when_worker_pause_polling_is_disabled()
    {
        if (! property_exists(Worker::class, 'pausable')) {
            $this->markTestSkipped('Worker::$pausable is not available on this Laravel version.');
        }

        $original = Worker::$pausable;

        try {
            Worker::$pausable = false;

            $capabilities = FrameworkCapabilities::detect();

            $this->assertFalse($capabilities->queuePausing);
            $this->assertFalse($capabilities->queuePauseFor);
        } finally {
            Worker::$pausable = $original;
        }
    }

    /**
     * Require framework QueueManager pause/resume/isPaused support.
     */
    private function requireQueuePausingSupport(): FrameworkCapabilities
    {
        $capabilities = FrameworkCapabilities::detect();

        if (! $capabilities->queuePausing) {
            $this->markTestSkipped('Queue pausing is not supported by the installed Laravel version.');
        }

        return $capabilities;
    }

    /**
     * Require framework support for timed queue pausing.
     */
    private function requireTimedQueuePausingSupport(): FrameworkCapabilities
    {
        $capabilities = $this->requireQueuePausingSupport();

        if (! $capabilities->queuePauseFor) {
            $this->markTestSkipped('Timed queue pausing is not supported by the installed Laravel version.');
        }

        return $capabilities;
    }

    /**
     * Whether the installed framework exposes the queue pausing APIs on QueueManager.
     */
    private function frameworkExposesQueuePausingApis(): bool
    {
        return method_exists(QueueManager::class, 'pause')
            && method_exists(QueueManager::class, 'resume')
            && method_exists(QueueManager::class, 'isPaused');
    }
}
