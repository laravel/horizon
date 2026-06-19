<?php

namespace Laravel\Horizon;

class LuaScripts
{
    /**
     * Update the metrics for a job.
     *
     * KEYS[1] - The name of the key being updated
     * KEYS[2] - The name of the key of the metrics group
     * ARGV[1] - The runtime in milliseconds of the current job
     *
     * @return string
     */
    public static function updateMetrics()
    {
        return <<<'LUA'
            redis.call('hsetnx', KEYS[1], 'throughput', 0)
            
            redis.call('sadd', KEYS[2], KEYS[1])
            
            local hash = redis.call('hmget', KEYS[1], 'throughput', 'runtime')

            local throughput = hash[1] + 1
            local runtime = 0

            if hash[2] then
                runtime = ((hash[1] * tonumber(hash[2])) + tonumber(ARGV[1])) / throughput
            else
                runtime = tonumber(ARGV[1])
            end

            redis.call('hmset', KEYS[1], 'throughput', throughput, 'runtime', runtime)
LUA;
    }

    /**
     * Atomically update the status of a retry entry in the retried_by hash field.
     *
     * KEYS[1] - The job hash key
     * ARGV[1] - The retry job ID to update
     * ARGV[2] - The new status ('failed' or 'completed')
     *
     * @return string
     */
    public static function updateRetryStatus()
    {
        return <<<'LUA'
            local retries = redis.call('hget', KEYS[1], 'retried_by')
            if not retries then return end
            local decoded = cjson.decode(retries)
            for i, retry in ipairs(decoded) do
                if retry['id'] == ARGV[1] then
                    retry['status'] = ARGV[2]
                    break
                end
            end
            redis.call('hset', KEYS[1], 'retried_by', cjson.encode(decoded))
LUA;
    }

    /**
     * Atomically append a retry reference to the retried_by hash field.
     *
     * KEYS[1] - The job hash key
     * ARGV[1] - The retry job ID
     * ARGV[2] - The current timestamp
     *
     * @return string
     */
    public static function storeRetryReference()
    {
        return <<<'LUA'
            local retries = redis.call('hget', KEYS[1], 'retried_by')
            local decoded = {}
            if retries then
                decoded = cjson.decode(retries)
            end
            table.insert(decoded, {id = ARGV[1], status = 'pending', retried_at = tonumber(ARGV[2])})
            redis.call('hset', KEYS[1], 'retried_by', cjson.encode(decoded))
LUA;
    }

    /**
     * Get the Lua script for purging recent and pending jobs off of the queue.
     *
     * KEYS[1] - The name of the recent jobs sorted set
     * KEYS[2] - The name of the pending jobs sorted set
     * ARGV[1] - The prefix of the Horizon keys
     * ARGV[2] - The name of the queue to purge
     *
     * @return string
     */
    public static function purge()
    {
        return <<<'LUA'
            local count = 0
            local cursor = ARGV[3]

            -- Iterate over the recent jobs sorted set
            local scanner = redis.call('zscan', KEYS[1], cursor)
            cursor = scanner[1]

            for i = 1, #scanner[2], 2 do
                local jobid = scanner[2][i]
                local hashkey = ARGV[1] .. jobid
                local job = redis.call('hmget', hashkey, 'status', 'queue')

                -- Delete the pending / reserved jobs in the given queue
                if((job[1] == 'reserved' or job[1] == 'pending') and job[2] == ARGV[2]) then
                    redis.call('zrem', KEYS[1], jobid)
                    redis.call('zrem', KEYS[2], jobid)
                    redis.call('del', hashkey)
                    count = count + 1
                end
            end

            return {count, cursor}
LUA;
    }
}
