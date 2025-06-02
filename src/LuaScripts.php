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
            local cursor = 0

            repeat
                -- Iterate over the recent jobs sorted set
                local scanner = redis.call('zscan', KEYS[1], cursor)
                cursor = scanner[1]

                for i = 1, #scanner[2], 2 do
                    local jobid = scanner[2][i]
                    local hashkey = ARGV[1] .. jobid
                    local job = redis.call('hmget', hashkey, 'status', 'queue')

                    -- Delete the pending/reserved jobs, that match the queue
                    -- name, from the sorted sets as well as the job hash
                    if((job[1] == 'reserved' or job[1] == 'pending') and job[2] == ARGV[2]) then
                        redis.call('zrem', KEYS[1], jobid)
                        redis.call('zrem', KEYS[2], jobid)
                        redis.call('del', hashkey)
                        count = count + 1
                    end
                end
            until cursor == '0'

            return count
LUA;
    }

    /**
     * Get the Lua script for purging specific recent and pending job off of the queue.
     *
     * KEYS[1] - The name of the recent jobs sorted set
     * KEYS[2] - The name of the pending jobs sorted set
     * ARGV[1] - The prefix of the Horizon keys
     * ARGV[2] - The name of the queue to purge
     * ARGV[3] - The namespace of the job to purge
     *
     * @return string
     */
    public static function purgeSpecificJob()
    {
        return <<<'LUA'

        local count = 0
        local cursor = 0

        repeat
            local scanner = redis.call('zscan', KEYS[1], cursor)
            cursor = scanner[1]

            for i = 1, #scanner[2], 2 do
                local jobId = scanner[2][i]
                local hashKey = ARGV[1] .. jobId
                local job = redis.call('hmget', hashKey, 'status', 'queue', 'name')

                local statusMatch = (job[1] == 'reserved' or job[1] == 'pending')
                local queueMatch = (job[2] == ARGV[2])
                local nameMatch = (job[3] == ARGV[3])

                if statusMatch and queueMatch and nameMatch then
                    redis.call('zrem', KEYS[1], jobId)
                    redis.call('zrem', KEYS[2], jobId)
                    redis.call('del', hashKey)
                    count = count + 1
                end
            end
        until cursor == '0'

        return count

        LUA;
    }

    /**
     * Get the Lua script for clear specific job off of the primary queue.
     *
     * KEYS[1] - The name of the primary queue
     * ARGV[1] - The namespace of the job to clear
     *
     * @return string
     */
    public static function clearSpecificJobFromPrimaryQueue()
    {
        return <<<'LUA'
            local items = redis.call('lrange', KEYS[1], 0, -1)
            local count = 0

            for _, item in ipairs(items) do
                local ok, data = pcall(cjson.decode, item)

                if ok and data.displayName == ARGV[1] then
                    redis.call('lrem', KEYS[1], 0, item)
                    count = count + 1
                end
            end

            return count
        LUA;
    }

    /**
     * Get the Lua script for clear specific job off of the delayed queue.
     *
     * KEYS[1] - The name of the delayed queue
     * ARGV[1] - The namespace of the job to clear
     *
     * @return string
     */
    public static function clearSpecificJobFromDelayedQueue()
    {
        return <<<'LUA'
            local items = redis.call('zrange', KEYS[1], 0, -1)
            local count = 0

            for _, item in ipairs(items) do
                local ok, data = pcall(cjson.decode, item)

                if ok and data.displayName == ARGV[1] then
                    redis.call('zrem', KEYS[1], item)
                    count = count + 1
                end
            end

            return count
        LUA;
    }

    /**
     * Get the Lua script for clear specific job off of the reserved queue.
     *
     * KEYS[1] - The name of the reserved queue
     * ARGV[1] - The namespace of the job to clear
     *
     * @return string
     */
    public static function clearSpecificJobFromReservedQueue()
    {
        return <<<'LUA'
            local items = redis.call('zrange', KEYS[1], 0, -1)
            local count = 0

            for _, item in ipairs(items) do
                local ok, data = pcall(cjson.decode, item)

                if ok and data.displayName == ARGV[1] then
                    redis.call('zrem', KEYS[1], item)
                    count = count + 1
                end
            end

            return count
        LUA;
    }

    /**
     * Get the Lua script for clear specific job off of the notify queue.
     *
     * KEYS[1] - The name of the notify queue
     *
     * @return string
     */
    public static function clearNotifyQueue()
    {
        return <<<'LUA'
            redis.call('del', KEYS[1])
        LUA;
    }
}
