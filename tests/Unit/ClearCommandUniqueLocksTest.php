<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Laravel\Horizon\Console\ClearCommand;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;
use ReflectionMethod;

class ClearCommandUniqueLocksTest extends UnitTest
{
    public function test_unique_job_lock_is_released_for_unique_jobs()
    {
        $lock = Mockery::mock(Lock::class);
        $lock->shouldReceive('forceRelease')->once();

        $cache = Mockery::mock(Cache::class);
        $cache->shouldReceive('lock')->once()->andReturn($lock);

        $job = new FakeUniqueJob;
        $payload = json_encode([
            'data' => [
                'command' => serialize($job),
            ],
        ]);

        $command = new ClearCommand;

        $method = new ReflectionMethod($command, 'releaseUniqueJobLock');
        $method->setAccessible(true);
        $method->invoke($command, $cache, $payload);
    }

    public function test_non_unique_job_does_not_release_lock()
    {
        $lock = Mockery::mock(Lock::class);
        $lock->shouldNotReceive('forceRelease');

        $cache = Mockery::mock(Cache::class);
        $cache->shouldNotReceive('lock');

        $job = new FakeNonUniqueJob;
        $payload = json_encode([
            'data' => [
                'command' => serialize($job),
            ],
        ]);

        $command = new ClearCommand;

        $method = new ReflectionMethod($command, 'releaseUniqueJobLock');
        $method->setAccessible(true);
        $method->invoke($command, $cache, $payload);
    }

    public function test_invalid_payload_does_not_throw()
    {
        $cache = Mockery::mock(Cache::class);
        $cache->shouldNotReceive('lock');

        $command = new ClearCommand;

        $method = new ReflectionMethod($command, 'releaseUniqueJobLock');
        $method->setAccessible(true);

        // Invalid JSON should not throw.
        $method->invoke($command, $cache, 'not-json');

        // Missing command key should not throw.
        $method->invoke($command, $cache, json_encode(['data' => []]));

        // This assertion is implicit - no exception means success.
        $this->assertTrue(true);
    }
}

class FakeUniqueJob implements ShouldBeUnique
{
    public $uniqueId = 'test-unique-id';
}

class FakeNonUniqueJob
{
    //
}
