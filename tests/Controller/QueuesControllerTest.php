<?php

namespace Laravel\Horizon\Tests\Controller;

use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Supervisor;
use Laravel\Horizon\SupervisorOptions;
use Laravel\Horizon\Tests\ControllerTest;

class QueuesControllerTest extends ControllerTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $supervisor = new Supervisor(new SupervisorOptions('risa:name', 'redis', 'high,default'));
        resolve(SupervisorRepository::class)->update($supervisor);
    }

    public function test_queue_can_be_paused_and_resumed()
    {
        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/queues/pause', ['connection' => 'redis', 'queue' => 'high'])
            ->assertOk();

        $this->assertTrue(Queue::isPaused('redis', 'high'));
        $this->assertFalse(Queue::isPaused('redis', 'default'));

        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/queues/resume', ['connection' => 'redis', 'queue' => 'high'])
            ->assertOk();

        $this->assertFalse(Queue::isPaused('redis', 'high'));
    }

    public function test_unknown_queue_cannot_be_paused()
    {
        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/queues/pause', ['connection' => 'redis', 'queue' => 'unknown'])
            ->assertNotFound();

        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/queues/pause', ['connection' => 'other', 'queue' => 'high'])
            ->assertNotFound();

        $this->assertFalse(Queue::isPaused('redis', 'unknown'));
        $this->assertFalse(Queue::isPaused('other', 'high'));
    }

    public function test_connection_and_queue_are_required()
    {
        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/queues/pause', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['connection', 'queue']);
    }

    public function test_workload_includes_paused_state()
    {
        Queue::pause('redis', 'high');

        $response = $this->actingAs(new Fakes\User)->get('/horizon/api/workload');

        $response->assertJson([
            [
                'name' => 'high,default',
                'connection' => 'redis',
                'paused' => false,
                'split_queues' => [
                    'high' => ['name' => 'high', 'paused' => true],
                    'default' => ['name' => 'default', 'paused' => false],
                ],
            ],
        ]);

        Queue::pause('redis', 'default');

        $this->actingAs(new Fakes\User)
            ->get('/horizon/api/workload')
            ->assertJsonPath('0.paused', true);
    }
}
