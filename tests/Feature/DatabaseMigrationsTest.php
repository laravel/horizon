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
            'horizon_metric_increments',
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

    public function test_metric_increments_table_has_required_columns_and_aggregation_index()
    {
        foreach (['id', 'key', 'kind', 'runtime', 'recorded_at'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('horizon_metric_increments', $column),
                "Expected [horizon_metric_increments.{$column}] column to exist."
            );
        }

        $this->assertTrue(
            Schema::hasIndex('horizon_metric_increments', ['key', 'id']),
            'Expected composite (key, id) index on horizon_metric_increments for aggregation + delete-by-id.'
        );
    }

    public function test_score_columns_are_integer_backed_and_job_references_has_composite_cursor_index()
    {
        foreach (['horizon_job_references', 'horizon_tags'] as $table) {
            $type = strtolower((string) Schema::getColumnType($table, 'score'));

            $this->assertTrue(
                str_contains($type, 'int'),
                "Expected {$table}.score to be an integer family column; got [{$type}]."
            );
        }

        $this->assertTrue(
            Schema::hasIndex('horizon_job_references', ['type', 'score', 'id']),
            'Expected composite (type, score, id) index on horizon_job_references to cover keyset cursor order.'
        );
    }
}
