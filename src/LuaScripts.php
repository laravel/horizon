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
     * https://redislabs.com/blog/the-7th-principle-of-redis-we-optimize-for-joy/
     * A fast, type-agnostic way to copy a Redis key
     *
     * KEYS[1] - The name of the source key
     * KEYS[2] - The name of the destination key
     * ARGV[1] - If equal to "NX" and the destination key exists, it will not be
     *           overridden and the copy operation will not be carried out.
     *
     * @return string|null "OK" on success, null otherwise
     */
    public static function copy()
    {
        return <<<'LUA'
            local s = KEYS[1]
            local d = KEYS[2]
            
            if redis.call("EXISTS", d) == 1 then
              if type(ARGV[1]) == "string" and ARGV[1]:upper() == "NX" then
                return nil
              else
                redis.call("DEL", d)
              end
            end
            
            redis.call("RESTORE", d, 0, redis.call("DUMP", s))
            return "OK"
LUA;
    }
}
