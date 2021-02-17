<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Laravel\Horizon\JobPayload;
use Laravel\Horizon\Tests\Unit\Fixtures\FakeEvent;
use Laravel\Horizon\Tests\Unit\Fixtures\FakeEventWithModel;
use Laravel\Horizon\Tests\Unit\Fixtures\FakeJobWithEloquentCollection;
use Laravel\Horizon\Tests\Unit\Fixtures\FakeJobWithEloquentModel;
use Laravel\Horizon\Tests\Unit\Fixtures\FakeJobWithTagsMethod;
use Laravel\Horizon\Tests\Unit\Fixtures\FakeListener;
use Laravel\Horizon\Tests\Unit\Fixtures\FakeListenerWithProperties;
use Laravel\Horizon\Tests\Unit\Fixtures\FakeListenerWithTypedProperties;
use Laravel\Horizon\Tests\Unit\Fixtures\FakeModel;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;
use StdClass;

class RedisPayloadTest extends UnitTest
{
    public function test_type_is_correctly_determined()
    {
        $JobPayload = new JobPayload(json_encode(['id' => 1]));

        $JobPayload->prepare(new BroadcastEvent(new StdClass));
        $this->assertSame('broadcast', $JobPayload->decoded['type']);

        $JobPayload->prepare(new CallQueuedListener('stdClass', 'method', [new StdClass]));
        $this->assertSame('event', $JobPayload->decoded['type']);

        $JobPayload->prepare(new SendQueuedMailable(Mockery::mock(Mailable::class)));
        $this->assertSame('mail', $JobPayload->decoded['type']);

        $JobPayload->prepare(new SendQueuedNotifications([], new StdClass, ['mail']));
        $this->assertSame('notification', $JobPayload->decoded['type']);
    }

    public function test_tags_are_correctly_determined()
    {
        $JobPayload = new JobPayload(json_encode(['id' => 1]));

        $first = new FakeModel;
        $first->id = 1;

        $second = new FakeModel;
        $second->id = 2;

        $JobPayload->prepare(new FakeJobWithEloquentModel($first, $second));
        $this->assertEquals([FakeModel::class.':1', FakeModel::class.':2'], $JobPayload->decoded['tags']);
    }

    public function test_tags_are_correctly_gathered_from_collections()
    {
        $JobPayload = new JobPayload(json_encode(['id' => 1]));

        $first = new FakeModel;
        $first->id = 1;

        $second = new FakeModel;
        $second->id = 2;

        $JobPayload->prepare(new FakeJobWithEloquentCollection(new EloquentCollection([$first, $second])));
        $this->assertEquals([FakeModel::class.':1', FakeModel::class.':2'], $JobPayload->decoded['tags']);
    }

    public function test_tags_are_correctly_extracted_for_internal_special_jobs()
    {
        $JobPayload = new JobPayload(json_encode(['id' => 1]));

        $first = new FakeModel;
        $first->id = 1;

        $second = new FakeModel;
        $second->id = 2;

        $JobPayload->prepare(new FakeJobWithEloquentCollection(new EloquentCollection([$first, $second])));
        $this->assertEquals([FakeModel::class.':1', FakeModel::class.':2'], $JobPayload->decoded['tags']);
    }

    public function test_tags_are_correctly_extracted_for_listeners()
    {
        $JobPayload = new JobPayload(json_encode(['id' => 1]));

        $job = new CallQueuedListener(FakeListener::class, 'handle', [new FakeEvent()]);

        $JobPayload->prepare($job);

        $this->assertEquals([
            'listenerTag1', 'listenerTag2', 'eventTag1', 'eventTag2',
        ], $JobPayload->decoded['tags']);
    }

    public function test_tags_are_correctly_determined_for_listeners()
    {
        $JobPayload = new JobPayload(json_encode(['id' => 1]));

        $job = new CallQueuedListener(FakeListenerWithProperties::class, 'handle', [new FakeEventWithModel(42)]);

        $JobPayload->prepare($job);

        $this->assertEquals([FakeModel::class.':42'], $JobPayload->decoded['tags']);
    }

    /**
     * @requires PHP 7.4
     */
    public function test_tags_are_correctly_determined_for_listeners_with_property_types()
    {
        $JobPayload = new JobPayload(json_encode(['id' => 1]));

        $job = new CallQueuedListener(FakeListenerWithTypedProperties::class, 'handle', [new FakeEventWithModel(21)]);

        $JobPayload->prepare($job);

        $this->assertEquals([FakeModel::class.':21'], $JobPayload->decoded['tags']);
    }

    public function test_listener_and_event_tags_can_merge_auto_tag_events()
    {
        $JobPayload = new JobPayload(json_encode(['id' => 1]));

        $job = new CallQueuedListener(FakeListener::class, 'handle', [new FakeEventWithModel(5)]);

        $JobPayload->prepare($job);

        $this->assertEquals([
            'listenerTag1', 'listenerTag2', FakeModel::class.':5',
        ], $JobPayload->decoded['tags']);
    }

    public function test_tags_are_added_to_existing()
    {
        $JobPayload = new JobPayload(json_encode(['id' => 1, 'tags' => ['mytag']]));

        $job = new CallQueuedListener(FakeListenerWithProperties::class, 'handle', [new FakeEventWithModel(42)]);

        $JobPayload->prepare($job);

        $this->assertEquals(['mytag', FakeModel::class.':42'], $JobPayload->decoded['tags']);
    }

    public function test_jobs_can_have_tags_method_to_override_auto_tagging()
    {
        $JobPayload = new JobPayload(json_encode(['id' => 1]));

        $JobPayload->prepare(new FakeJobWithTagsMethod);
        $this->assertEquals(['first', 'second'], $JobPayload->decoded['tags']);
    }
}
