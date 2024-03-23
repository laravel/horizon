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
     * Get the Lua script to purge a specific job from the queue using its job id.
     *
     * KEYS[1] - The recent jobs sorted set
     * KEYS[2] - The pending jobs sorted set
     * ARGV[1] - The prefix of the Horizon keys
     * ARGV[2] - The queue to purge
     * ARGV[3] - The id of the job to purge
     *
     * @return string
     */
    public static function purgeSpecific()
    {
        return <<<'LUA'
        -- Ensure that the correct number of ARGV values and KEYS values are provided
        assert(#ARGV >= 3, "Three ARGV values are required: [Horizon key prefix, queue name, job id]")
        assert(#KEYS >= 2, "Two KEYS values are required: [name of sorted set for recent jobs, name of sorted set for pending jobs]")

        -- Retrieve the specific job id
        local jobid = ARGV[3]

        -- Construct the hash key using the prefix and the job id
        local hashkey = ARGV[1] .. jobid

        -- Retrieve the 'status' and 'queue' fields for the job
        local job = redis.call('hmget', hashkey, 'status', 'queue')

        -- Ensure 'status' and 'queue' fields are retrieved
        assert(#job == 2, "hmget was unable to retrieve both 'status' and 'queue' fields for the provided job id")

        -- If the job is reserved or pending, and the job's queue matches the one to be purged
        if ((job[1] == 'reserved' or job[1] == 'pending') and job[2] == ARGV[2]) then
            -- Remove job id from the recent and pending jobs sorted sets
            redis.call('zrem', KEYS[1], jobid)
            redis.call('zrem', KEYS[2], jobid)

            -- Delete the hash for the job
            redis.call('del', hashkey)

            -- Return "1" to indicate successful removal
            return 1
        end

        -- Return "0" to indicate no job was removed
        return 0
LUA;
    }

}
