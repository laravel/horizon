<?php

namespace Laravel\Horizon\Repositories;

use Illuminate\Contracts\Redis\Factory as RedisFactory;

class RedisMonitoringMetadataRepository
{
    public function __construct(public RedisFactory $redis)
    {
    }

    /**
     * Read metadata only for the monitoring dashboard, never job admission.
     */
    public function get(array $tags)
    {
        if ($tags === []) {
            return [];
        }

        $connection = $this->redis->connection('horizon');
        $records = $connection->hmget('monitoring:metadata', $tags);
        $reasons = $connection->hmget('monitoring:reasons', $tags);
        $metadata = [];

        foreach (array_values($tags) as $index => $tag) {
            $record = json_decode($records[$index] ?: '{}', true);
            $reason = $reasons[$index];
            $metadata[$tag] = [
                'reason' => is_string($reason) ? $reason : ($record['reason'] ?? null),
                'created_by' => $record['created_by'] ?? null,
                'created_at' => $record['created_at'] ?? null,
            ];
        }

        return $metadata;
    }

    public function create($tag, $reason = null, $createdBy = null)
    {
        // Keep creation details together and immutable when a monitor is added twice.
        $this->redis->connection('horizon')->hsetnx('monitoring:metadata', $tag, json_encode([
            'reason' => $reason,
            'created_by' => $createdBy,
            'created_at' => now()->toIso8601String(),
        ]));
    }

    public function updateReason($tag, $reason)
    {
        $this->redis->connection('horizon')->hset('monitoring:reasons', $tag, $reason);
    }

    public function forget($tag)
    {
        $connection = $this->redis->connection('horizon');
        $connection->hdel('monitoring:metadata', $tag);
        $connection->hdel('monitoring:reasons', $tag);
    }
}
