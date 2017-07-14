<?php

namespace Laravel\Horizon\Tests\Unit;

use Mockery;
use StdClass;
use Laravel\Horizon\JobPayload;
use Laravel\Horizon\Tests\UnitTest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class RedisPayloadTest extends UnitTest
{
    public function test_type_is_correctly_determined()
    {
        $JobPayload = new JobPayload(json_encode(['id' => 1]));

        $JobPayload->prepare(new BroadcastEvent(new StdClass));
        $this->assertEquals('broadcast', $JobPayload->decoded['type']);

        $JobPayload->prepare(new CallQueuedListener('Class', 'method', [new StdClass]));
        $this->assertEquals('event', $JobPayload->decoded['type']);

        $JobPayload->prepare(new SendQueuedMailable(Mockery::mock(Mailable::class)));
        $this->assertEquals('mail', $JobPayload->decoded['type']);

        $JobPayload->prepare(new SendQueuedNotifications([], new StdClass, ['mail']));
        $this->assertEquals('notification', $JobPayload->decoded['type']);
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


    public function test_listener_and_event_tags_can_merge_auto_tag_events()
    {
        $JobPayload = new JobPayload(json_encode(['id' => 1]));

        $job = new CallQueuedListener(FakeListener::class, 'handle', [new FakeEventWithModel(5)]);

        $JobPayload->prepare($job);

        $this->assertEquals([
            'listenerTag1', 'listenerTag2', FakeModel::class.':5',
        ], $JobPayload->decoded['tags']);
    }


    public function test_jobs_can_have_tags_method_to_override_auto_tagging()
    {
        $JobPayload = new JobPayload(json_encode(['id' => 1]));

        $JobPayload->prepare(new FakeJobWithTagsMethod);
        $this->assertEquals(['first', 'second'], $JobPayload->decoded['tags']);
    }
}


class FakeJobWithTagsMethod
{
    public function tags()
    {
        return ['first', 'second'];
    }
}


class FakeJobWithEloquentModel
{
    public $nonModel;
    public $first;
    public $second;

    public function __construct($first, $second)
    {
        $this->nonModel = 1;
        $this->first = $first;
        $this->second = $second;
    }
}


class FakeJobWithEloquentCollection
{
    public $collection;

    public function __construct($collection)
    {
        $this->collection = $collection;
    }
}


class FakeModel extends Model
{
    //
}

class FakeEvent
{
    public function tags()
    {
        return ['eventTag1', 'eventTag2'];
    }
}

class FakeListener
{
    public function tags()
    {
        return ['listenerTag1', 'listenerTag2'];
    }
}

class FakeEventWithModel
{
    public $model;

    public function __construct($id)
    {
        $this->model = new FakeModel;
        $this->model->id = $id;
    }
}
