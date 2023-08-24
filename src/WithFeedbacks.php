<?php

namespace Laravel\Horizon;

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Stringable;

trait WithFeedbacks
{

    public function afterFeedback(mixed $onSuccess = null, mixed $onFailure = null): void
    {
        $job = $this->job;
        if (!$job instanceof RedisJob) {
            return;
        }
        $backtrace = debug_backtrace();
        if ($onSuccess !== null) {
            Queue::after(function (JobProcessed $e) use ($backtrace, $job, $onSuccess) {
                if ($e->job->uuid() === $job->uuid()) {
                    $this->mergeFeedbacksToPayload($backtrace, $onSuccess);
                }
            });
        }
        if ($onFailure !== null) {
            Queue::failing(function (JobFailed $e) use ($backtrace, $job, $onFailure) {
                if ($e->job->uuid() === $job->uuid()) {
                    $this->mergeFeedbacksToPayload($backtrace, $onFailure);
                }
            });
        }
    }

    public function feedback(mixed $feedback): void
    {
        $this->mergeFeedbacksToPayload(debug_backtrace(), $feedback);
    }

    public function feedbackIf(bool $condition, mixed $feedback): void
    {
        if ($condition) {
            $this->mergeFeedbacksToPayload(debug_backtrace(), $feedback);
        }
    }

    protected function getFeedbackCaller(array $backtrace): string
    {
        $caller = array_shift($backtrace);
        $file = Arr::get($caller, 'file', 'unknown');
        $line = Arr::get($caller, 'line', false);
        return str($file)
            ->whenStartsWith(base_path(), function (Stringable $str) {
                return $str->remove(base_path() . DIRECTORY_SEPARATOR);
            })
            ->append($line ? ':' . $line : '')
            ->toString();
    }

    protected function mergeFeedbacksToPayload(array $backtrace, mixed $feedback): void
    {
        $job = $this->job;
        if (!$job instanceof RedisJob) {
            return;
        }
        $uuid = $job->uuid();
        /** @var \Illuminate\Redis\Connections\PhpRedisConnection $connection */
        $connection = $job->getRedisQueue()->getRedis()->connection('horizon');
        if (!$connection->exists($uuid)) {
            return;
        }
        $payload = json_decode($connection->hMGet($uuid, ['payload'])[0], true);
        $feedbacks = collect(Arr::get($payload, 'feedbacks', []));
        $payload['feedbacks'] = $feedbacks->push([
            'content' => $feedback,
            'time' => strval(microtime(true)),
            'where' => $this->getFeedbackCaller($backtrace),
        ])->toArray();
        $connection->hmset($uuid, ['payload' => json_encode($payload)]);
    }
}