<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Laravel\Horizon\Tests\DatabaseIntegrationTest;

class DatabaseMigrationsTest extends DatabaseIntegrationTest
{
    public function test_required_horizon_tables_exist_after_migration()
    {
        $expected = [
            'horizon_jobs',
            'horizon_job_references',
            'horizon_tags',
            'horizon_monitored_tags',
            'horizon_metrics',
            'horizon_metric_snapshots',
            'horizon_states',
            'horizon_master_supervisors',
            'horizon_supervisors',
            'horizon_processes',
            'horizon_locks',
            'horizon_commands',
        ];

        foreach ($expected as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Expected Horizon table [{$table}] to exist after running migrations."
            );
        }
    }
}
