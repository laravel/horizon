<?php

namespace Laravel\Horizon\Tests\Controller;

use Laravel\Horizon\RedisQueue;
use Laravel\Horizon\Repositories\RedisJobRepository;
use Mockery;

class QueueClearControllerTest extends AbstractControllerTest
{
    public function test_it_removes_all_job_from_specific_queue()
    {
        Mockery::mock(RedisJobRepository::class)
            ->shouldReceive('purge')
            ->withArgs(['email-processing']);

        Mockery::mock(RedisQueue::class)
            ->shouldReceive('clear')
            ->withArgs(['email-processing']);
        
        $this->actingAs(new Fakes\User)
            ->post('/horizon/api/clearQueue', ['queue' => 'email-processing']);
    }
}
