<?php

namespace Laravel\Horizon\Tests\Controller;

use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Tests\ControllerTest;

class QueueControllerTest extends ControllerTest
{
    public function test_pause_endpoint_pauses_queue()
    {
        Queue::shouldReceive('pause')
            ->once()
            ->with('redis', 'default');

        $response = $this->actingAs(new Fakes\User)
            ->post('/horizon/api/queues/pause', [
                'connection' => 'redis',
                'queue' => 'default',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    public function test_pause_endpoint_requires_connection()
    {
        $response = $this->actingAs(new Fakes\User)
            ->post('/horizon/api/queues/pause', [
                'queue' => 'default',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('connection');
    }

    public function test_pause_endpoint_requires_queue()
    {
        $response = $this->actingAs(new Fakes\User)
            ->post('/horizon/api/queues/pause', [
                'connection' => 'redis',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('queue');
    }

    public function test_resume_endpoint_resumes_queue()
    {
        Queue::shouldReceive('resume')
            ->once()
            ->with('redis', 'default');

        $response = $this->actingAs(new Fakes\User)
            ->post('/horizon/api/queues/resume', [
                'connection' => 'redis',
                'queue' => 'default',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    public function test_resume_endpoint_requires_connection()
    {
        $response = $this->actingAs(new Fakes\User)
            ->post('/horizon/api/queues/resume', [
                'queue' => 'default',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('connection');
    }

    public function test_resume_endpoint_requires_queue()
    {
        $response = $this->actingAs(new Fakes\User)
            ->post('/horizon/api/queues/resume', [
                'connection' => 'redis',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('queue');
    }

    public function test_status_endpoint_returns_pause_status()
    {
        Queue::shouldReceive('isPaused')
            ->once()
            ->with('redis', 'default')
            ->andReturn(true);

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon/api/queues/status/redis/default');

        $response->assertStatus(200);
        $response->assertJson([
            'is_paused' => true,
            'connection' => 'redis',
            'queue' => 'default',
        ]);
    }

    public function test_status_endpoint_returns_active_status()
    {
        Queue::shouldReceive('isPaused')
            ->once()
            ->with('redis', 'default')
            ->andReturn(false);

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon/api/queues/status/redis/default');

        $response->assertStatus(200);
        $response->assertJson([
            'is_paused' => false,
            'connection' => 'redis',
            'queue' => 'default',
        ]);
    }
}
