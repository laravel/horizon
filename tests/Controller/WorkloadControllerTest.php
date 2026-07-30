<?php

namespace Laravel\Horizon\Tests\Controller;

use Illuminate\Queue\QueueManager;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Tests\ControllerTest;
use Mockery;

class WorkloadControllerTest extends ControllerTest
{
    public function test_workload_exposes_queue_pause_state_when_supported()
    {
        if (! Horizon::supportsQueuePausing()) {
            $this->markTestSkipped('Basic queue pausing APIs are not available on this Laravel version.');
        }

        $this->app->instance(WorkloadRepository::class, $this->workload([
            [
                'connection' => 'redis',
                'name' => 'reports',
                'length' => 3,
                'reserved' => 1,
                'delayed' => 0,
                'wait' => 2,
                'processes' => 1,
                'split_queues' => null,
            ],
        ]));

        $queues = app(QueueManager::class);

        try {
            $queues->pause('redis', 'reports');

            $this->actingAs(new Fakes\User)
                ->getJson('/horizon/api/workload')
                ->assertOk()
                ->assertExactJson([
                    [
                        'connection' => 'redis',
                        'name' => 'reports',
                        'length' => 3,
                        'reserved' => 1,
                        'delayed' => 0,
                        'wait' => 2,
                        'processes' => 1,
                        'split_queues' => null,
                        'queue_pausing_supported' => true,
                        'timed_queue_pausing_supported' => Horizon::supportsTimedQueuePausing(),
                        'paused' => true,
                    ],
                ]);
        } finally {
            $queues->resume('redis', 'reports');
        }
    }

    public function test_workload_exposes_split_queue_pause_state_when_supported()
    {
        if (! Horizon::supportsQueuePausing()) {
            $this->markTestSkipped('Basic queue pausing APIs are not available on this Laravel version.');
        }

        $this->app->instance(WorkloadRepository::class, $this->workload([
            [
                'connection' => 'redis',
                'name' => 'emails,notifications',
                'length' => 4,
                'reserved' => 1,
                'delayed' => 0,
                'wait' => 2,
                'processes' => 2,
                'split_queues' => [
                    [
                        'name' => 'emails',
                        'length' => 3,
                        'wait' => 2,
                    ],
                    [
                        'name' => 'notifications',
                        'length' => 1,
                        'wait' => 1,
                    ],
                ],
            ],
        ]));

        $queues = app(QueueManager::class);

        try {
            $queues->pause('redis', 'notifications');

            $this->actingAs(new Fakes\User)
                ->getJson('/horizon/api/workload')
                ->assertOk()
                ->assertExactJson([
                    [
                        'connection' => 'redis',
                        'name' => 'emails,notifications',
                        'length' => 4,
                        'reserved' => 1,
                        'delayed' => 0,
                        'wait' => 2,
                        'processes' => 2,
                        'split_queues' => [
                            [
                                'name' => 'emails',
                                'length' => 3,
                                'wait' => 2,
                                'connection' => 'redis',
                                'queue_pausing_supported' => true,
                                'timed_queue_pausing_supported' => Horizon::supportsTimedQueuePausing(),
                                'paused' => false,
                            ],
                            [
                                'name' => 'notifications',
                                'length' => 1,
                                'wait' => 1,
                                'connection' => 'redis',
                                'queue_pausing_supported' => true,
                                'timed_queue_pausing_supported' => Horizon::supportsTimedQueuePausing(),
                                'paused' => true,
                            ],
                        ],
                        'queue_pausing_supported' => true,
                        'timed_queue_pausing_supported' => Horizon::supportsTimedQueuePausing(),
                        'paused' => null,
                    ],
                ]);
        } finally {
            $queues->resume('redis', 'notifications');
        }
    }

    public function test_workload_accepts_custom_repository_shape_without_connection()
    {
        $this->app->instance(WorkloadRepository::class, $this->workload([
            [
                'name' => 'legacy',
                'length' => 2,
                'wait' => 1,
                'processes' => 1,
                'split_queues' => null,
            ],
        ]));

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/workload')
            ->assertOk()
            ->assertExactJson([
                [
                    'name' => 'legacy',
                    'length' => 2,
                    'wait' => 1,
                    'processes' => 1,
                    'split_queues' => null,
                    'queue_pausing_supported' => false,
                    'timed_queue_pausing_supported' => false,
                    'paused' => null,
                ],
            ]);
    }

    public function test_workload_normalizes_collection_split_queues_without_connection()
    {
        $this->app->instance(WorkloadRepository::class, $this->workload([
            [
                'name' => 'emails,notifications',
                'length' => 5,
                'wait' => 3,
                'processes' => 2,
                'split_queues' => collect([
                    [
                        'name' => 'emails',
                        'length' => 3,
                        'wait' => 2,
                    ],
                    [
                        'name' => 'notifications',
                        'length' => 2,
                        'wait' => 1,
                    ],
                ]),
            ],
        ]));

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/workload')
            ->assertOk()
            ->assertExactJson([
                [
                    'name' => 'emails,notifications',
                    'length' => 5,
                    'wait' => 3,
                    'processes' => 2,
                    'split_queues' => [
                        [
                            'name' => 'emails',
                            'length' => 3,
                            'wait' => 2,
                            'queue_pausing_supported' => false,
                            'timed_queue_pausing_supported' => false,
                            'paused' => null,
                        ],
                        [
                            'name' => 'notifications',
                            'length' => 2,
                            'wait' => 1,
                            'queue_pausing_supported' => false,
                            'timed_queue_pausing_supported' => false,
                            'paused' => null,
                        ],
                    ],
                    'queue_pausing_supported' => false,
                    'timed_queue_pausing_supported' => false,
                    'paused' => null,
                ],
            ]);
    }

    public function test_workload_marks_queue_pausing_unsupported_when_capability_is_disabled()
    {
        if (! method_exists(QueueManager::class, 'pause')
            || ! method_exists(QueueManager::class, 'resume')
            || ! method_exists(QueueManager::class, 'isPaused')) {
            $this->app->instance(WorkloadRepository::class, $this->workload([
                [
                    'connection' => 'redis',
                    'name' => 'reports',
                    'length' => 1,
                    'reserved' => 0,
                    'delayed' => 0,
                    'wait' => 0,
                    'processes' => 1,
                    'split_queues' => null,
                ],
            ]));

            $this->assertFalse(Horizon::supportsQueuePausing());

            $this->actingAs(new Fakes\User)
                ->getJson('/horizon/api/workload')
                ->assertOk()
                ->assertExactJson([
                    [
                        'connection' => 'redis',
                        'name' => 'reports',
                        'length' => 1,
                        'reserved' => 0,
                        'delayed' => 0,
                        'wait' => 0,
                        'processes' => 1,
                        'split_queues' => null,
                        'queue_pausing_supported' => false,
                        'timed_queue_pausing_supported' => false,
                        'paused' => null,
                    ],
                ]);

            return;
        }

        if (! property_exists(\Illuminate\Queue\Worker::class, 'pausable')) {
            $this->markTestSkipped('Worker::$pausable is not available on this Laravel version.');
        }

        $original = \Illuminate\Queue\Worker::$pausable;
        \Illuminate\Queue\Worker::$pausable = false;

        try {
            $this->assertFalse(Horizon::supportsQueuePausing());

            $this->app->instance(WorkloadRepository::class, $this->workload([
                [
                    'connection' => 'redis',
                    'name' => 'reports',
                    'length' => 1,
                    'reserved' => 0,
                    'delayed' => 0,
                    'wait' => 0,
                    'processes' => 1,
                    'split_queues' => null,
                ],
            ]));

            $this->actingAs(new Fakes\User)
                ->getJson('/horizon/api/workload')
                ->assertOk()
                ->assertExactJson([
                    [
                        'connection' => 'redis',
                        'name' => 'reports',
                        'length' => 1,
                        'reserved' => 0,
                        'delayed' => 0,
                        'wait' => 0,
                        'processes' => 1,
                        'split_queues' => null,
                        'queue_pausing_supported' => false,
                        'timed_queue_pausing_supported' => false,
                        'paused' => null,
                    ],
                ]);
        } finally {
            \Illuminate\Queue\Worker::$pausable = $original;
        }
    }

    private function workload(array $queues): WorkloadRepository
    {
        $workload = Mockery::mock(WorkloadRepository::class);
        $workload->shouldReceive('get')->once()->andReturn($queues);

        return $workload;
    }
}
