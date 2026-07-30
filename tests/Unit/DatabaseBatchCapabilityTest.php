<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Once;
use Laravel\Horizon\Batches\DatabaseBatchCapability;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;
use RuntimeException;

class DatabaseBatchCapabilityTest extends UnitTest
{
    protected function tearDown(): void
    {
        Once::flush();

        parent::tearDown();
    }

    public function test_custom_batch_repository_is_available()
    {
        $repository = Mockery::mock(BatchRepository::class);

        $this->assertTrue((new DatabaseBatchCapability($repository))->available());
    }

    public function test_database_repository_is_unavailable_when_source_table_is_missing()
    {
        $schema = Mockery::mock(Builder::class);
        $schema->shouldReceive('hasTable')->once()->with('job_batches')->andReturn(false);

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getSchemaBuilder')->once()->andReturn($schema);

        $repository = Mockery::mock(DatabaseBatchRepository::class);
        $repository->shouldReceive('getConnection')->once()->andReturn($connection);

        $this->assertFalse((new DatabaseBatchCapability($repository))->available());
    }

    public function test_database_repository_is_available_when_source_table_exists()
    {
        $schema = Mockery::mock(Builder::class);
        $schema->shouldReceive('hasTable')->once()->with('job_batches')->andReturn(true);

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getSchemaBuilder')->once()->andReturn($schema);

        $repository = Mockery::mock(DatabaseBatchRepository::class);
        $repository->shouldReceive('getConnection')->once()->andReturn($connection);

        $this->assertTrue((new DatabaseBatchCapability($repository))->available());
    }

    public function test_schema_inspection_failure_is_treated_as_unavailable()
    {
        $repository = Mockery::mock(DatabaseBatchRepository::class);
        $repository->shouldReceive('getConnection')
            ->once()
            ->andThrow(new RuntimeException('database offline'));

        $this->assertFalse((new DatabaseBatchCapability($repository))->available());
    }
}
