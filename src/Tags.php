<?php

namespace Laravel\Horizon;

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use ReflectionClass;
use ReflectionProperty;
use stdClass;

class Tags
{
    /**
     * The event that was last handled.
     *
     * @var object|null
     */
    protected static $event;

    /**
     * Determine the tags for the given job.
     *
     * @param  mixed  $job
     * @return array
     */
    public static function for($job)
    {
        if ($tags = static::extractExplicitTags($job)) {
            return $tags;
        }

        return static::modelsFor(static::targetsFor($job))->map(function ($model) {
            return get_class($model).':'.$model->getKey();
        })->all();
    }

    /**
     * Extract tags from job object.
     *
     * @param  mixed  $job
     * @return array
     */
    public static function extractExplicitTags($job)
    {
        return $job instanceof CallQueuedListener
                    ? static::tagsForListener($job)
                    : static::explicitTags(static::targetsFor($job));
    }

    /**
     * Determine tags for the given queued listener.
     *
     * @param  mixed  $job
     * @return array
     */
    protected static function tagsForListener($job)
    {
        $event = static::extractEvent($job);

        static::setEvent($event);

        return collect(
            [static::extractListener($job), $event]
        )->map(function ($job) {
            return static::for($job);
        })->collapse()->unique()->tap(function () {
            static::flushEventState();
        })->toArray();
    }

    /**
     * Determine tags for the given job.
     *
     * @param  array  $jobs
     * @return array
     */
    protected static function explicitTags(array $jobs)
    {
        return collect($jobs)->map(function ($job) {
            return method_exists($job, 'tags') ? $job->tags(static::$event) : [];
        })->collapse()->unique()->all();
    }

    /**
     * Get the actual target for the given job.
     *
     * @param  mixed  $job
     * @return array
     */
    public static function targetsFor($job)
    {
        return match (true) {
            $job instanceof BroadcastEvent => [$job->event],
            $job instanceof CallQueuedListener => [static::extractEvent($job)],
            $job instanceof SendQueuedMailable => [$job->mailable],
            $job instanceof SendQueuedNotifications => [$job->notification],
            default => [$job],
        };
    }

    /**
     * Get the models from the given object.
     *
     * @param  array  $targets
     * @return \Illuminate\Support\Collection
     */
    public static function modelsFor(array $targets)
    {
        $models = [];

        foreach ($targets as $target) {
            $models[] = collect(
                (new ReflectionClass($target))->getProperties()
            )->map(function ($property) use ($target) {
                $property->setAccessible(true);

                $value = static::getValue($property, $target);

                if ($value instanceof Model) {
                    return [$value];
                } elseif ($value instanceof EloquentCollection) {
                    return $value->all();
                }
            })->collapse()->filter()->all();
        }

        return collect($models)->collapse()->unique();
    }

    /**
     * Get the value of the given ReflectionProperty.
     *
     * @param  \ReflectionProperty  $property
     * @param  mixed  $target
     */
    protected static function getValue(ReflectionProperty $property, $target)
    {
        if (method_exists($property, 'isInitialized') &&
            ! $property->isInitialized($target)) {
            return;
        }

        return $property->getValue($target);
    }

    /**
     * Extract the listener from a queued job.
     *
     * @param  mixed  $job
     * @return mixed
     */
    protected static function extractListener($job)
    {
        return (new ReflectionClass($job->class))->newInstanceWithoutConstructor();
    }

    /**
     * Extract the event from a queued job.
     *
     * @param  mixed  $job
     * @return mixed
     */
    protected static function extractEvent($job)
    {
        return isset($job->data[0]) && is_object($job->data[0])
                        ? $job->data[0]
                        : new stdClass;
    }

    /**
     * Set the event currently being handled.
     *
     * @param  object  $event
     * @return void
     */
    protected static function setEvent($event)
    {
        static::$event = $event;
    }

    /**
     * Flush the event currently being handled.
     *
     * @return void
     */
    protected static function flushEventState()
    {
        static::$event = null;
    }
}
