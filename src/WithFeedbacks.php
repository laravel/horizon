<?php

namespace Laravel\Horizon;

use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;

trait WithFeedbacks
{

    /**
     * Feedback register to do a feedback after job is done.
     *
     * @param  mixed|null  $onSuccess
     * @param  mixed|null  $onFailure
     * @return void
     */
    public function afterFeedback(mixed $onSuccess = null, mixed $onFailure = null): void
    {
        $job = $this->job;
        if (!$job instanceof RedisJob) {
            return;
        }
        $backtrace = debug_backtrace();
        if ($onSuccess !== null) {
            Queue::after(function ($e) use ($backtrace, $job, $onSuccess) {
                if ($e->job->uuid() === $job->uuid()) {
                    $this->mergeFeedbacksToPayload($backtrace, $onSuccess);
                }
            });
        }
        if ($onFailure !== null) {
            Queue::failing(function ($e) use ($backtrace, $job, $onFailure) {
                if ($e->job->uuid() === $job->uuid()) {
                    $this->mergeFeedbacksToPayload($backtrace, $onFailure);
                }
            });
        }
    }

    /**
     * Do a feedback.
     *
     * @param  mixed  $feedback
     * @return void
     * @throws \RedisException
     */
    public function feedback(mixed $feedback): void
    {
        $this->mergeFeedbacksToPayload(debug_backtrace(), $feedback);
    }

    /**
     * Do a conditional feedback.
     *
     * @param  bool  $condition
     * @param  mixed  $feedback
     * @return void
     * @throws \RedisException
     */
    public function feedbackIf(bool $condition, mixed $feedback): void
    {
        if ($condition) {
            $this->mergeFeedbacksToPayload(debug_backtrace(), $feedback);
        }
    }

    /**
     * Get the file and line that called the feedback.
     *
     * @param  array  $backtrace
     * @return string
     */
    protected function getFeedbackCaller(array $backtrace): string
    {
        $caller = array_shift($backtrace);
        $file = Arr::get($caller, 'file', 'unknown');
        $line = Arr::get($caller, 'line', false);
        return str($file)
            ->whenStartsWith(base_path(), function ($str) {
                return $str->remove(base_path() . DIRECTORY_SEPARATOR);
            })
            ->append($line ? ':' . $line : '')
            ->toString();
    }

    /**
     * Merge a feedback into feedbacks in job payload.
     *
     * @param  array  $backtrace
     * @param  mixed  $feedback
     * @return void
     * @throws \RedisException
     */
    protected function mergeFeedbacksToPayload(array $backtrace, mixed $feedback): void
    {
        $job = $this->job;
        if (!$job instanceof RedisJob) {
            return;
        }
        $uuid = $job->uuid();
        $connection = $job->getRedisQueue()->getRedis()->connection('horizon');
        if (!$connection->exists($uuid)) {
            return;
        }
        $payload = json_decode($connection->hmget($uuid, ['payload'])[0], true);
        $feedbacks = collect(Arr::get($payload, 'feedbacks', []));
        $payload['feedbacks'] = $feedbacks->push([
            'content' => $feedback,
            'time' => strval(microtime(true)),
            'where' => $this->getFeedbackCaller($backtrace),
        ])->toArray();
        $connection->hmset($uuid, ['payload' => json_encode($payload)]);
    }
}