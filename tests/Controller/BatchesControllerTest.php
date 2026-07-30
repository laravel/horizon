<?php

namespace Laravel\Horizon\Tests\Controller;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Horizon\Tests\ControllerTest;

class BatchesControllerTest extends ControllerTest
{
    public function test_batches_can_be_searched_by_name()
    {
        $this->setupBatchTable();
        $this->seedBatches();

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon/api/batches?query=Import');

        $response->assertOk();
        $this->assertTrue($response->original['search_supported']);

        $batches = $response->original['batches'];

        $this->assertCount(1, $batches);
        $this->assertSame('Import Users', $batches[0]->name);
    }

    public function test_batches_can_be_searched_by_id()
    {
        $this->setupBatchTable();
        $this->seedBatches();

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon/api/batches?query=batch-2');

        $response->assertOk();
        $this->assertTrue($response->original['search_supported']);

        $batches = $response->original['batches'];

        $this->assertCount(1, $batches);
        $this->assertSame('Send Emails', $batches[0]->name);
    }

    public function test_search_escapes_like_wildcards()
    {
        $this->setupBatchTable();
        $this->seedBatches();

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon/api/batches?query=%25');

        $response->assertOk();
        $this->assertTrue($response->original['search_supported']);

        $this->assertEmpty($response->original['batches']);
    }

    public function test_search_supports_cursor_pagination()
    {
        $this->setupBatchTable();

        for ($i = 1; $i <= 3; $i++) {
            $this->insertBatch("batch-{$i}", 'Import Chunk '.$i);
        }

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon/api/batches?query=Import&before_id=batch-3');

        $response->assertOk();
        $this->assertTrue($response->original['search_supported']);

        $batches = $response->original['batches'];

        $this->assertCount(2, $batches);
        $this->assertSame('batch-2', $batches[0]->id);
        $this->assertSame('batch-1', $batches[1]->id);
    }

    public function test_batch_overview_returns_the_latest_three_active_previews()
    {
        $this->setupBatchTable();

        $this->insertBatchRow('batch-5', 'Archive Audit Logs', 100, 40, 0, null, 500);
        $this->insertBatchRow('batch-4', 'Send Reports', 20, 10, 2, null, 400);
        $this->insertBatchRow('batch-3', '', 10, 10, 0, null, 300);
        $this->insertBatchRow('batch-2', 'Failure Stalled', 10, 2, 2, null, 200);
        $this->insertBatchRow('batch-1', 'Cancelled', 10, 5, 0, 100, 100);
        $this->insertBatchRow('batch-0', 'Finished', 10, 0, 0, null, 50);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/batches/overview')
            ->assertOk()
            ->assertExactJson([
                'available' => true,
                'active' => 3,
                'previews' => [
                    ['id' => 'batch-5', 'name' => 'Archive Audit Logs', 'progress' => 60],
                    ['id' => 'batch-4', 'name' => 'Send Reports', 'progress' => 50],
                    ['id' => 'batch-3', 'name' => 'batch-3', 'progress' => 0],
                ],
            ]);
    }

    public function test_batch_overview_limits_to_the_latest_three_of_four_active_batches()
    {
        $this->setupBatchTable();

        $this->insertBatchRow('batch-oldest', 'Oldest Active', 10, 5, 0, null, 100);
        $this->insertBatchRow('batch-mid-a', 'Middle Active A', 10, 6, 0, null, 200);
        $this->insertBatchRow('batch-mid-b', 'Middle Active B', 10, 7, 0, null, 300);
        $this->insertBatchRow('batch-newest', 'Newest Active', 10, 8, 0, null, 400);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/batches/overview')
            ->assertOk()
            ->assertExactJson([
                'available' => true,
                'active' => 4,
                'previews' => [
                    ['id' => 'batch-newest', 'name' => 'Newest Active', 'progress' => 20],
                    ['id' => 'batch-mid-b', 'name' => 'Middle Active B', 'progress' => 30],
                    ['id' => 'batch-mid-a', 'name' => 'Middle Active A', 'progress' => 40],
                ],
            ]);
    }

    public function test_missing_batch_table_returns_unavailable_overview_shape()
    {
        $this->app['config']->set('queue.batching.database', 'testing');
        $this->app['config']->set('queue.batching.table', 'missing_job_batches');
        $this->app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/batches/overview')
            ->assertOk()
            ->assertExactJson([
                'available' => false,
                'active' => null,
                'previews' => [],
            ]);
    }

    public function test_dynamodb_batching_returns_unavailable_overview_shape()
    {
        $this->app['config']->set('queue.batching.driver', 'dynamodb');

        $this->app->instance(
            \Illuminate\Bus\BatchRepository::class,
            \Mockery::mock(\Illuminate\Bus\BatchRepository::class)
        );

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/batches/overview')
            ->assertOk()
            ->assertExactJson([
                'available' => false,
                'active' => null,
                'previews' => [],
            ]);
    }

    public function test_missing_batch_table_marks_batch_listing_unavailable()
    {
        $this->app['config']->set('queue.batching.database', 'testing');
        $this->app['config']->set('queue.batching.table', 'missing_job_batches');
        $this->app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/batches')
            ->assertOk()
            ->assertExactJson([
                'batches' => [],
                'available' => false,
                'search_supported' => false,
            ]);
    }

    public function test_empty_batch_table_marks_batch_listing_available()
    {
        $this->setupBatchTable();

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/batches')
            ->assertOk()
            ->assertExactJson([
                'batches' => [],
                'available' => true,
                'search_supported' => true,
            ]);
    }

    public function test_dynamodb_batching_lists_batches_without_search_support()
    {
        $this->app['config']->set('queue.batching.driver', 'dynamodb');

        $batch = (object) [
            'id' => 'dynamo-batch-1',
            'name' => 'Dynamo finished review batch',
        ];

        $repository = \Mockery::mock(\Illuminate\Bus\BatchRepository::class);
        $repository->shouldReceive('get')
            ->once()
            ->with(50, null)
            ->andReturn([$batch]);
        $repository->shouldNotReceive('find');

        $this->app->instance(\Illuminate\Bus\BatchRepository::class, $repository);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/batches')
            ->assertOk()
            ->assertExactJson([
                'batches' => [
                    [
                        'id' => 'dynamo-batch-1',
                        'name' => 'Dynamo finished review batch',
                    ],
                ],
                'available' => true,
                'search_supported' => false,
            ]);
    }

    public function test_dynamodb_batching_does_not_execute_relational_search()
    {
        $this->setupBatchTable();
        $this->seedBatches();
        $this->app['config']->set('queue.batching.driver', 'dynamodb');

        $repository = \Mockery::mock(\Illuminate\Bus\BatchRepository::class);
        $repository->shouldNotReceive('get');
        $repository->shouldNotReceive('find');

        $this->app->instance(\Illuminate\Bus\BatchRepository::class, $repository);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/batches?query=Import')
            ->assertOk()
            ->assertExactJson([
                'batches' => [],
                'available' => true,
                'search_supported' => false,
            ]);

        $this->assertSame(3, DB::connection('testing')->table('job_batches')->count());
    }

    private function setupBatchTable()
    {
        $this->app['config']->set('queue.batching.database', 'testing');
        $this->app['config']->set('queue.batching.table', 'job_batches');
        $this->app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        Schema::connection('testing')->create('job_batches', static function ($table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });
    }

    private function seedBatches()
    {
        $this->insertBatch('batch-1', 'Import Users');
        $this->insertBatch('batch-2', 'Send Emails');
        $this->insertBatch('batch-3', 'Process Orders');
    }

    private function insertBatch($id, $name)
    {
        $this->insertBatchRow($id, $name, 10, 0, 0, null, time());
    }

    private function insertBatchRow($id, $name, $totalJobs, $pendingJobs, $failedJobs, $cancelledAt, $createdAt)
    {
        DB::connection('testing')
            ->table('job_batches')
            ->insert([
                'id' => $id,
                'name' => $name,
                'total_jobs' => $totalJobs,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'failed_job_ids' => '[]',
                'options' => serialize([]),
                'created_at' => $createdAt,
                'cancelled_at' => $cancelledAt,
                'finished_at' => null,
            ]);
    }
}
