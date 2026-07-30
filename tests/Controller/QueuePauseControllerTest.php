<?php

namespace Laravel\Horizon\Tests\Controller;

use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Tests\ControllerTest;

class QueuePauseControllerTest extends ControllerTest
{
    public function test_supported_queue_pausing_can_pause_and_resume_a_queue()
    {
        if (! $this->basicQueuePausingIsAvailable()) {
            $this->markTestSkipped('Basic queue pausing APIs are not available on this Laravel version.');
        }

        $queues = app(QueueManager::class);

        $this->actingAs(new Fakes\User)
            ->post('/horizon/api/queues/redis/reports/pause')
            ->assertOk()
            ->assertExactJson([
                'connection' => 'redis',
                'queue' => 'reports',
                'paused' => true,
            ]);

        $this->assertTrue($queues->isPaused('redis', 'reports'));

        $this->actingAs(new Fakes\User)
            ->delete('/horizon/api/queues/redis/reports/pause')
            ->assertOk()
            ->assertExactJson([
                'connection' => 'redis',
                'queue' => 'reports',
                'paused' => false,
            ]);

        $this->assertFalse($queues->isPaused('redis', 'reports'));
    }

    public function test_queue_pausing_supports_queue_names_with_slashes()
    {
        if (! $this->basicQueuePausingIsAvailable()) {
            $this->markTestSkipped('Basic queue pausing APIs are not available on this Laravel version.');
        }

        $queues = app(QueueManager::class);
        $queue = 'reports/daily';

        $this->actingAs(new Fakes\User)
            ->post('/horizon/api/queues/redis/'.rawurlencode($queue).'/pause')
            ->assertOk()
            ->assertExactJson([
                'connection' => 'redis',
                'queue' => $queue,
                'paused' => true,
            ]);

        $this->assertTrue($queues->isPaused('redis', $queue));

        $this->actingAs(new Fakes\User)
            ->delete('/horizon/api/queues/redis/'.rawurlencode($queue).'/pause')
            ->assertOk()
            ->assertExactJson([
                'connection' => 'redis',
                'queue' => $queue,
                'paused' => false,
            ]);

        $this->assertFalse($queues->isPaused('redis', $queue));
    }

    public function test_timed_queue_pausing_accepts_a_duration_in_minutes()
    {
        if (! $this->basicQueuePausingIsAvailable() || ! method_exists(QueueManager::class, 'pauseFor')) {
            $this->markTestSkipped('Timed queue pausing APIs are not available on this Laravel version.');
        }

        $queues = app(QueueManager::class);

        try {
            $this->travelTo(now());

            $this->actingAs(new Fakes\User)
                ->postJson('/horizon/api/queues/redis/reports/pause', [
                    'duration_minutes' => 30,
                ])
                ->assertOk()
                ->assertExactJson([
                    'connection' => 'redis',
                    'queue' => 'reports',
                    'paused' => true,
                ]);

            $this->assertTrue($queues->isPaused('redis', 'reports'));

            $this->travel(31)->minutes();

            $this->assertFalse($queues->isPaused('redis', 'reports'));
        } finally {
            $this->travelBack();
            $queues->resume('redis', 'reports');
        }
    }

    public function test_timed_queue_pausing_accepts_duration_boundaries()
    {
        if (! $this->basicQueuePausingIsAvailable() || ! method_exists(QueueManager::class, 'pauseFor')) {
            $this->markTestSkipped('Timed queue pausing APIs are not available on this Laravel version.');
        }

        $queues = app(QueueManager::class);

        try {
            $this->actingAs(new Fakes\User)
                ->postJson('/horizon/api/queues/redis/reports/pause', [
                    'duration_minutes' => 1,
                ])
                ->assertOk()
                ->assertExactJson([
                    'connection' => 'redis',
                    'queue' => 'reports',
                    'paused' => true,
                ]);

            $this->assertTrue($queues->isPaused('redis', 'reports'));

            $queues->resume('redis', 'reports');

            $this->actingAs(new Fakes\User)
                ->postJson('/horizon/api/queues/redis/reports/pause', [
                    'duration_minutes' => 525600,
                ])
                ->assertOk()
                ->assertExactJson([
                    'connection' => 'redis',
                    'queue' => 'reports',
                    'paused' => true,
                ]);

            $this->assertTrue($queues->isPaused('redis', 'reports'));
        } finally {
            $queues->resume('redis', 'reports');
        }
    }

    public function test_timed_queue_pausing_rejects_an_invalid_duration()
    {
        if (! $this->basicQueuePausingIsAvailable()) {
            $this->markTestSkipped('Basic queue pausing APIs are not available on this Laravel version.');
        }

        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/queues/redis/reports/pause', [
                'duration_minutes' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('duration_minutes');

        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/queues/redis/reports/pause', [
                'duration_minutes' => 525601,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('duration_minutes');

        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/queues/redis/reports/pause', [
                'duration_minutes' => 'abc',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('duration_minutes');
    }

    public function test_queue_pausing_rejects_an_unknown_connection()
    {
        if (! $this->basicQueuePausingIsAvailable()) {
            $this->markTestSkipped('Basic queue pausing APIs are not available on this Laravel version.');
        }

        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/queues/unsupported/reports/pause')
            ->assertStatus(422)
            ->assertJsonValidationErrors('connection');
    }

    public function test_queue_pausing_is_unavailable_when_framework_lacks_basic_pause_apis()
    {
        if ($this->basicQueuePausingApisExist()) {
            $this->markTestSkipped('Basic queue pausing APIs are available on this Laravel version.');
        }

        $this->assertFalse(Horizon::supportsQueuePausing());
        $this->assertFalse(Horizon::supportsTimedQueuePausing());

        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/queues/redis/reports/pause')
            ->assertNotFound();

        $this->actingAs(new Fakes\User)
            ->deleteJson('/horizon/api/queues/redis/reports/pause')
            ->assertNotFound();
    }

    public function test_supports_queue_pausing_reports_false_when_worker_pause_polling_is_disabled()
    {
        if (! $this->basicQueuePausingApisExist()) {
            $this->markTestSkipped('Basic queue pausing APIs are not available on this Laravel version.');
        }

        if (! property_exists(Worker::class, 'pausable')) {
            $this->markTestSkipped('Worker::$pausable is not available on this Laravel version.');
        }

        $original = Worker::$pausable;

        try {
            Worker::$pausable = false;

            $this->assertFalse(Horizon::supportsQueuePausing());
            $this->assertFalse(Horizon::supportsTimedQueuePausing());
        } finally {
            Worker::$pausable = $original;
        }
    }

    public function test_pause_and_resume_endpoints_are_unavailable_when_worker_pause_polling_is_disabled()
    {
        if (! $this->basicQueuePausingApisExist()) {
            $this->markTestSkipped('Basic queue pausing APIs are not available on this Laravel version.');
        }

        if (! property_exists(Worker::class, 'pausable')) {
            $this->markTestSkipped('Worker::$pausable is not available on this Laravel version.');
        }

        $this->assertTrue(Horizon::supportsQueuePausing());

        $original = Worker::$pausable;
        $queues = app(QueueManager::class);

        try {
            Worker::$pausable = false;

            $this->assertFalse(Horizon::supportsQueuePausing());

            $this->actingAs(new Fakes\User)
                ->postJson('/horizon/api/queues/redis/reports/pause')
                ->assertNotFound();

            $this->assertFalse($queues->isPaused('redis', 'reports'));

            $queues->pause('redis', 'reports');
            $this->assertTrue($queues->isPaused('redis', 'reports'));

            $this->actingAs(new Fakes\User)
                ->deleteJson('/horizon/api/queues/redis/reports/pause')
                ->assertNotFound();

            $this->assertTrue($queues->isPaused('redis', 'reports'));
        } finally {
            Worker::$pausable = $original;

            if (method_exists($queues, 'resume')) {
                $queues->resume('redis', 'reports');
            }
        }
    }

    public function test_supports_queue_pausing_reports_true_when_indefinite_pause_apis_are_available()
    {
        if (! $this->basicQueuePausingApisExist()) {
            $this->assertFalse(Horizon::supportsQueuePausing());

            return;
        }

        if (property_exists(Worker::class, 'pausable')) {
            $original = Worker::$pausable;
            Worker::$pausable = true;
        }

        try {
            $this->assertTrue(method_exists(QueueManager::class, 'pause'));
            $this->assertTrue(method_exists(QueueManager::class, 'resume'));
            $this->assertTrue(method_exists(QueueManager::class, 'isPaused'));
            $this->assertTrue(Horizon::supportsQueuePausing());
        } finally {
            if (isset($original)) {
                Worker::$pausable = $original;
            }
        }
    }

    public function test_supports_timed_queue_pausing_requires_pause_for()
    {
        if (! $this->basicQueuePausingApisExist()) {
            $this->assertFalse(Horizon::supportsTimedQueuePausing());

            return;
        }

        if (property_exists(Worker::class, 'pausable')) {
            $original = Worker::$pausable;
            Worker::$pausable = true;
        }

        try {
            $this->assertSame(
                method_exists(QueueManager::class, 'pauseFor'),
                Horizon::supportsTimedQueuePausing()
            );
        } finally {
            if (isset($original)) {
                Worker::$pausable = $original;
            }
        }
    }

    protected function basicQueuePausingIsAvailable()
    {
        return Horizon::supportsQueuePausing();
    }

    protected function basicQueuePausingApisExist()
    {
        return method_exists(QueueManager::class, 'pause')
            && method_exists(QueueManager::class, 'resume')
            && method_exists(QueueManager::class, 'isPaused');
    }
}
