<?php

namespace Laravel\Horizon;

use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Support\Arr;

trait WithFeedbacks
{

    protected function mergeFeedbacksToPayload(array $feedback): void
    {
        /** @var \Illuminate\Queue\Jobs\RedisJob $job */
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
        $payload['feedbacks'] = $feedbacks->push($feedback)->toArray();
        $connection->hmset($uuid, ['payload' => json_encode($payload)]);
    }
}