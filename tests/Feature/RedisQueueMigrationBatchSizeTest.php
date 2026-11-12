<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Tests\Feature\Jobs\BasicJob;
use Laravel\Horizon\Tests\IntegrationTest;

class RedisQueueMigrationBatchSizeTest extends IntegrationTest
{
    public function test_migration_batch_size_limits_how_many_due_delayed_jobs_are_migrated_at_once()
    {
        config(['queue.connections.redis.migration_batch_size' => 2]);

        $migrated = $this->migrateFiveDueJobsOnce();

        $this->assertSame(2, $migrated->pendingSize('default'));
        $this->assertSame(3, $migrated->delayedSize('default'));
    }

    public function test_all_due_delayed_jobs_are_migrated_by_default()
    {
        $migrated = $this->migrateFiveDueJobsOnce();

        $this->assertSame(5, $migrated->pendingSize('default'));
        $this->assertSame(0, $migrated->delayedSize('default'));
    }

    protected function migrateFiveDueJobsOnce()
    {
        $queue = Queue::connection('redis');

        for ($i = 0; $i < 5; $i++) {
            $queue->later(now()->subSecond(), new BasicJob);
        }

        $key = $queue->getQueue('default');
        $queue->migrateExpiredJobs($key.':delayed', $key);

        return $queue;
    }
}
