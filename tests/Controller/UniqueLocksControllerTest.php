<?php

namespace Laravel\Horizon\Tests\Controller;

use Illuminate\Support\Facades\Cache;
use Laravel\Horizon\Tests\ControllerTest;
use Laravel\Horizon\Tests\Feature\Jobs\UniqueJob;
use Orchestra\Testbench\Attributes\DefineEnvironment;

class UniqueLocksControllerTest extends ControllerTest
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('cache.default', 'redis');
        $app['config']->set('cache.prefix', 'horizon-test-cache:');
    }

    public function test_unique_locks_are_listed_with_their_pending_job()
    {
        UniqueJob::dispatch('order-42');

        $response = $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/unique-locks')
            ->assertOk()
            ->assertJsonPath('supported', true)
            ->assertJsonCount(1, 'locks')
            ->assertJsonPath('locks.0.key', 'laravel_unique_job:'.UniqueJob::class.':order-42')
            ->assertJsonPath('locks.0.name', UniqueJob::class)
            ->assertJsonPath('locks.0.unique_id', 'order-42');

        $this->assertNotNull($response->json('locks.0.job_id'));
        $this->assertNotEmpty($response->json('locks.0.owner'));
        $this->assertGreaterThan(3500, $response->json('locks.0.expires_in'));
    }

    public function test_lock_without_pending_job_has_no_job()
    {
        Cache::lock('laravel_unique_job:App\\Jobs\\Stuck:7', 60)->get();
        Cache::put('unrelated-key', 'value');

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/unique-locks')
            ->assertJsonCount(1, 'locks')
            ->assertJsonPath('locks.0.name', 'App\\Jobs\\Stuck')
            ->assertJsonPath('locks.0.job_id', null);
    }

    public function test_unique_lock_can_be_released()
    {
        UniqueJob::dispatch('order-42');

        $this->assertFalse(Cache::lock('laravel_unique_job:'.UniqueJob::class.':order-42')->get());

        $this->actingAs(new Fakes\User)
            ->deleteJson('/horizon/api/unique-locks', ['key' => 'laravel_unique_job:'.UniqueJob::class.':order-42'])
            ->assertOk();

        $this->assertTrue(Cache::lock('laravel_unique_job:'.UniqueJob::class.':order-42')->get());
    }

    public function test_only_unique_job_locks_can_be_released()
    {
        Cache::lock('some-other-lock', 60)->get();

        $this->actingAs(new Fakes\User)
            ->deleteJson('/horizon/api/unique-locks', ['key' => 'some-other-lock'])
            ->assertUnprocessable();

        $this->assertFalse(Cache::lock('some-other-lock')->get());
    }

    #[DefineEnvironment('usesDatabaseCache')]
    public function test_unique_locks_are_listed_from_the_database_store()
    {
        $this->loadLaravelMigrations();

        UniqueJob::dispatch('invoice-7');
        Cache::lock('some-other-lock', 60)->get();

        $response = $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/unique-locks')
            ->assertJsonPath('supported', true)
            ->assertJsonCount(1, 'locks')
            ->assertJsonPath('locks.0.unique_id', 'invoice-7');

        $this->assertNotNull($response->json('locks.0.job_id'));

        $this->actingAs(new Fakes\User)
            ->deleteJson('/horizon/api/unique-locks', ['key' => 'laravel_unique_job:'.UniqueJob::class.':invoice-7'])
            ->assertOk();

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/unique-locks')
            ->assertJsonCount(0, 'locks');
    }

    protected function usesDatabaseCache($app)
    {
        $app['config']->set('cache.default', 'database');
    }

    public function test_unsupported_cache_stores_are_reported()
    {
        config(['cache.default' => 'array']);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/unique-locks')
            ->assertOk()
            ->assertJson(['supported' => false, 'locks' => []]);
    }
}
