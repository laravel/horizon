<?php

namespace Laravel\Horizon\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Laravel\Horizon\Repositories\RedisJobRepository;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;

class RedisJobRepositoryTest extends UnitTest
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_it_counts_failed_jobs_from_the_past_hour()
    {
        $now = CarbonImmutable::parse('2026-07-28 12:00:00', 'UTC');
        CarbonImmutable::setTestNow($now);

        $container = new Container;
        $container->instance('config', new ConfigRepository([
            'horizon.trim.failed' => 10080,
        ]));
        Container::setInstance($container);

        $redis = Mockery::mock(RedisFactory::class);
        $connection = Mockery::mock();

        $redis->shouldReceive('connection')->once()->with('horizon')->andReturn($connection);
        $connection->shouldReceive('zcount')
            ->once()
            ->with('failed_jobs', '-inf', $now->subHour()->getTimestamp() * -1)
            ->andReturn(3);

        $this->assertSame(3, (new RedisJobRepository($redis))->countFailedSince(60));
    }
}
