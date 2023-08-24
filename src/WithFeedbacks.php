<?php

namespace Laravel\Horizon;

use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;

trait WithFeedbacks
{

    protected ?array $feedbacksBag = null;

    protected mixed $onFailureFeedback = null;

    protected array|null $onFailureFeedbackBacktrace = null;

    protected mixed $onSuccessFeedback = null;

    protected array|null $onSuccessFeedbackBacktrace = null;

    /**
     * Feedback register to do a feedback after job is done.
     *
     * @param  mixed|null  $onSuccess
     * @param  mixed|null  $onFailure
     * @return void
     */
    public function afterFeedback(mixed $onSuccess = null, mixed $onFailure = null): void
    {
        $this->initFeedback();
        if (!empty($onSuccess)) {
            $this->onSuccessFeedback = $onSuccess;
            $this->onSuccessFeedbackBacktrace = debug_backtrace();
        }
        if (!empty($onFailure)) {
            $this->onFailureFeedback = $onFailure;
            $this->onFailureFeedbackBacktrace = debug_backtrace();
        }
    }

    /**
     * Do a feedback.
     *
     * @param  mixed  $feedback
     * @return void
     */
    public function feedback(mixed $feedback): void
    {
        $this->toFeedbackBag($feedback, debug_backtrace(), strval(microtime(true)));
    }

    /**
     * Do a conditional feedback.
     *
     * @param  bool  $condition
     * @param  mixed  $feedback
     * @return void
     */
    public function feedbackIf(bool $condition, mixed $feedback): void
    {
        if ($condition) {
            $this->toFeedbackBag($feedback, debug_backtrace(), strval(microtime(true)));
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
     * Initialize feedbacks bag and put job events callback to push feedback to payload after.
     *
     * @return void
     */
    protected function initFeedback(): void
    {
        if ($this->feedbacksBag !== null) {
            return;
        }
        $this->feedbacksBag = [];
        $job = $this->job;
        Queue::after(function ($e) use ($job) {
            if ($e->job->uuid() === $job->uuid()) {
                if ($this->onSuccessFeedback) {
                    $this->toFeedbackBag($this->onSuccessFeedback, $this->onSuccessFeedbackBacktrace, strval(microtime(true)));
                }
                $this->mergeFeedbacksToPayload();
            }
        });
        Queue::failing(function ($e) use ($job) {
            if ($e->job->uuid() === $job->uuid()) {
                if ($this->onFailureFeedback) {
                    $this->toFeedbackBag($this->onFailureFeedback, $this->onFailureFeedbackBacktrace, strval(microtime(true)));
                }
                $this->mergeFeedbacksToPayload();
            }
        });
    }

    /**
     * Merge the feedback bag into job payload.
     *
     * @return void
     * @throws \RedisException
     */
    protected function mergeFeedbacksToPayload(): void
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
        $payload['feedbacks'] = $this->feedbacksBag;
        $connection->hmset($uuid, ['payload' => json_encode($payload)]);
    }

    /**
     * Put a feedback into feedbacks bag.
     *
     * @param  mixed  $feedback
     * @param  array  $backtrace
     * @param  string  $time
     * @return void
     */
    protected function toFeedbackBag(mixed $feedback, array $backtrace, string $time): void
    {
        $this->feedbacksBag[] = [
            'content' => $feedback,
            'time' => $time,
            'where' => $this->getFeedbackCaller($backtrace),
        ];
    }
}