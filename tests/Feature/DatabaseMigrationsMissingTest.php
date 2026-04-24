<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Tests\DatabaseIntegrationTest;

class DatabaseMigrationsMissingTest extends DatabaseIntegrationTest
{
    protected function defineDatabaseMigrations()
    {
        // Intentionally do not run the package migrations so we can
        // assert the framework error surface is what we expect.
    }

    public function test_query_without_horizon_tables_throws_query_exception()
    {
        $this->expectException(QueryException::class);

        DB::table('horizon_jobs')->count();
    }
}
