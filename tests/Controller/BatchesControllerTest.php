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

        $batches = $response->original['batches'];

        $this->assertCount(2, $batches);
        $this->assertSame('batch-2', $batches[0]->id);
        $this->assertSame('batch-1', $batches[1]->id);
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
        DB::connection('testing')
            ->table('job_batches')
            ->insert([
                'id' => $id,
                'name' => $name,
                'total_jobs' => 10,
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'failed_job_ids' => '[]',
                'options' => serialize([]),
                'created_at' => time(),
                'cancelled_at' => null,
                'finished_at' => null,
            ]);
    }
}
