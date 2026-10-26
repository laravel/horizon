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

    /**
     * Get the Lua script for removing all jobs from a queue and returning their payloads.
     *
     * KEYS[1] - The queue we are clearing
     * KEYS[2] - The queue of delayed jobs
     * KEYS[3] - The queue of reserved jobs
     * KEYS[4] - The notification list for the queue
     * ARGV[1] - The current UNIX timestamp, reservations at or before it have expired
     *
     * @return string
     */
    public static function clear()
    {
        return <<<'LUA'
            local size = redis.call('llen', KEYS[1]) + redis.call('zcard', KEYS[2]) + redis.call('zcard', KEYS[3])

            local jobs = redis.call('lrange', KEYS[1], 0, -1)

            for _, job in ipairs(redis.call('zrange', KEYS[2], 0, -1)) do
                table.insert(jobs, job)
            end

            for _, job in ipairs(redis.call('zrangebyscore', KEYS[3], '-inf', ARGV[1])) do
                table.insert(jobs, job)
            end

            redis.call('del', KEYS[1], KEYS[2], KEYS[3], KEYS[4])

            return {size, jobs}
LUA;
    }
}
