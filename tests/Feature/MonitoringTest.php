<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Jobs\MonitorTag;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\Jobs\StopMonitoringTag;
use Laravel\Horizon\Contracts\TagRepository;

class MonitoringTest extends IntegrationTest
{
    public function test_can_retrieve_all_monitored_tags()
    {
        $repository = resolve(TagRepository::class);

        dispatch(new MonitorTag('first'));
        $this->assertEquals(['first'], $repository->monitoring());

        dispatch(new MonitorTag('second'));
        $monitored = $repository->monitoring();
        $this->assertTrue(in_array('first', $monitored));
        $this->assertTrue(in_array('second', $monitored));
        $this->assertCount(2, $monitored);
    }

    public function test_can_determine_if_a_set_of_tags_are_being_monitored()
    {
        $repository = resolve(TagRepository::class);
        dispatch(new MonitorTag('first'));
        $this->assertEquals(['first'], $repository->monitored(['first', 'second']));
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
        $this->assertEquals(1, $this->monitoredJobs('first'));
        $this->assertEquals(-1, Redis::connection('horizon')->ttl($id));
    }

    public function test_completed_jobs_are_removed_from_database_when_their_tag_is_no_longer_monitored()
    {
        dispatch(new MonitorTag('first'));
        Queue::push(new Jobs\BasicJob);
        $this->work();
        dispatch(new StopMonitoringTag('first'));
        $this->assertEquals(0, $this->monitoredJobs('first'));
    }
}
