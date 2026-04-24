<?php

namespace Laravel\Horizon\Tests\Feature\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Laravel\Horizon\Enums\JobStatus;
use Laravel\Horizon\Models\HorizonJob;
use Laravel\Horizon\Tests\DatabaseIntegrationTest;

class PruneCommandTest extends DatabaseIntegrationTest
{
    public function test_prune_command_deletes_expired_models_across_tables()
    {
        HorizonJob::create([
            'id' => (string) Str::uuid(),
            'queue' => 'default',
            'status' => JobStatus::Completed,
            'payload' => '{}',
            'expires_at' => CarbonImmutable::now()->subMinute(),
        ]);

        $this->artisan('horizon:prune')->assertSuccessful();

        $this->assertSame(0, HorizonJob::count());
    }

    public function test_prune_command_with_pretend_does_not_delete()
    {
        HorizonJob::create([
            'id' => (string) Str::uuid(),
            'queue' => 'default',
            'status' => JobStatus::Completed,
            'payload' => '{}',
            'expires_at' => CarbonImmutable::now()->subMinute(),
        ]);

        $this->artisan('horizon:prune', ['--pretend' => true])->assertSuccessful();

        $this->assertSame(1, HorizonJob::count());
    }

    public function test_prune_command_deletes_large_batches_in_full()
    {
        $rows = [];
        for ($i = 0; $i < 1_100; $i++) {
            $rows[] = [
                'id' => (string) Str::uuid(),
                'queue' => 'default',
                'status' => JobStatus::Completed->value,
                'payload' => '{}',
                'expires_at' => CarbonImmutable::now()->subMinute(),
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ];
        }

        HorizonJob::insert($rows);

        $this->artisan('horizon:prune')->assertSuccessful();

        $this->assertSame(0, HorizonJob::count());
    }
}
