<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Jobs\MonitorTag;
use Laravel\Horizon\Jobs\StopMonitoringTag;
use Laravel\Horizon\Tests\IntegrationTest;

class MonitoringTest extends IntegrationTest
{
    public function test_can_retrieve_all_monitored_tags()
    {
        $repository = resolve(TagRepository::class);

        dispatch(new MonitorTag('first'));
        $this->assertEquals(['first'], $repository->monitoring());

        dispatch(new MonitorTag('second'));
        $monitored = $repository->monitoring();
        $this->assertContains('first', $monitored);
        $this->assertContains('second', $monitored);
        $this->assertCount(2, $monitored);
    }

    public function test_can_determine_if_a_set_of_tags_are_being_monitored()
    {
        $repository = resolve(TagRepository::class);
        dispatch(new MonitorTag('first'));
        $this->assertEquals(['first'], $repository->monitored(['first', 'second']));
    }

    public function test_monitored_lookup_preserves_keys_and_duplicates_and_sees_changes_immediately()
    {
        $repository = resolve(TagRepository::class);
        $tags = [3 => 'first', 7 => 'second', 'last' => 'first'];

        $this->assertSame([], $repository->monitored($tags));
        $repository->monitor('first');
        $this->assertSame([3 => 'first', 'last' => 'first'], $repository->monitored($tags));
        $repository->stopMonitoring('first');
        $repository->monitor('second');
        $this->assertSame([7 => 'second'], $repository->monitored($tags));
        $repository->stopMonitoring('second');
        $this->assertSame([], $repository->monitored($tags));
    }

    public function test_monitored_lookup_keeps_the_original_tag_values()
    {
        $repository = resolve(TagRepository::class);
        $repository->monitor('0');
        $repository->monitor('');
        $tags = [2 => 0, 4 => '0', 6 => '00', 8 => ''];

        $this->assertSame([2 => 0, 4 => '0', 8 => ''], $repository->monitored($tags));
    }

    public function test_monitored_lookup_supports_phpredis_serialized_members()
    {
        $connection = Redis::connection('horizon');

        if (! $connection instanceof PhpRedisConnection || ! $connection->client() instanceof \Redis) {
            $this->markTestSkipped('A standalone PhpRedis connection is required.');
        }

        $client = $connection->client();
        $serializer = $client->getOption(\Redis::OPT_SERIALIZER);
        $client->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
        $repository = resolve(TagRepository::class);

        try {
            $repository->monitor('first');
            $this->assertSame([2 => 'first'], $repository->monitored([2 => 'first', 4 => 'second']));
            $repository->stopMonitoring('first');
            $this->assertSame([], $repository->monitored(['first']));
        } finally {
            $connection->del('monitoring');
            $client->setOption(\Redis::OPT_SERIALIZER, $serializer);
        }
    }

    public function test_can_stop_monitoring_tags()
    {
        $repository = resolve(TagRepository::class);
        dispatch(new MonitorTag('first'));
        dispatch(new StopMonitoringTag('first'));
        $this->assertEquals([], $repository->monitored(['first', 'second']));
    }

    public function test_tags_that_are_removed_from_monitoring_are_removed_from_storage()
    {
        dispatch(new MonitorTag('first'));
        dispatch(new StopMonitoringTag('first'));
        $this->assertNull(Redis::connection('horizon')->get('first'));
    }

    public function test_completed_jobs_are_stored_in_database_when_one_of_their_tags_is_being_monitored()
    {
        dispatch(new MonitorTag('first'));
        $id = Queue::push(new Jobs\BasicJob);
        $this->work();
        $this->assertSame(1, $this->monitoredJobs('first'));
        $this->assertGreaterThan(0, Redis::connection('horizon')->ttl($id));
    }

    public function test_completed_jobs_are_removed_from_database_when_their_tag_is_no_longer_monitored()
    {
        dispatch(new MonitorTag('first'));
        Queue::push(new Jobs\BasicJob);
        $this->work();
        dispatch(new StopMonitoringTag('first'));
        $this->assertSame(0, $this->monitoredJobs('first'));
    }

    public function test_all_completed_jobs_are_removed_from_database_when_their_tag_is_no_longer_monitored()
    {
        dispatch(new MonitorTag('first'));

        for ($i = 0; $i < 80; $i++) {
            Queue::push(new Jobs\BasicJob);
        }

        $this->work();

        dispatch(new StopMonitoringTag('first'));
        $this->assertSame(0, $this->monitoredJobs('first'));
    }
}
