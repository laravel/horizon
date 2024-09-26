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
            
            local throughput = tonumber(hash[1]) or 0
            
            throughput = throughput + 1
            
            local runtime = 0
            if hash[2] then
                runtime = ((tonumber(hash[1]) * tonumber(hash[2])) + tonumber(ARGV[1])) / throughput
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
            local batch_size = tonumber(100)
            local start = tonumber(0)
            
            -- Use ZRANGE to get a range of elements from the sorted set in a deterministic way
            local jobs = redis.call('zrange', KEYS[1], start, start + batch_size - 1)
            
            for i = 1, #jobs do
                local jobid = jobs[i]
                local hashkey = ARGV[1] .. jobid
                local job = redis.call('hmget', hashkey, 'status', 'queue')
            
                -- Delete the pending/reserved jobs that match the queue name
                if ((job[1] == 'reserved' or job[1] == 'pending') and job[2] == ARGV[2]) then
                    redis.call('zrem', KEYS[1], jobid)
                    redis.call('zrem', KEYS[2], jobid)
                    redis.call('del', hashkey)
                    count = count + 1
                end
            end
            
            return count
LUA;
    }
}
