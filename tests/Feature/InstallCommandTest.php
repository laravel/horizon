<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Support\ComposerAssetHook;
use Laravel\Horizon\Support\ComposerAssetHookResult;
use Laravel\Horizon\Tests\IntegrationTest;
use Mockery;

class InstallCommandTest extends IntegrationTest
{
    public function test_install_adds_composer_asset_hook_and_reports_success()
    {
        $hook = Mockery::mock(ComposerAssetHook::class);
        $hook->shouldReceive('ensure')
            ->once()
            ->with(base_path('composer.json'))
            ->andReturn(ComposerAssetHookResult::Added);

        $this->app->instance(ComposerAssetHook::class, $hook);

        $this->artisan('horizon:install')
            ->expectsOutputToContain('Added the Horizon asset refresh Composer hook.')
            ->expectsOutputToContain('Horizon scaffolding installed successfully.')
            ->assertSuccessful();
    }

    public function test_install_skips_composer_hook_when_opted_out()
    {
        $hook = Mockery::mock(ComposerAssetHook::class);
        $hook->shouldNotReceive('ensure');

        $this->app->instance(ComposerAssetHook::class, $hook);

        $this->artisan('horizon:install', ['--no-composer-hook' => true])
            ->doesntExpectOutputToContain('Added the Horizon asset refresh Composer hook.')
            ->doesntExpectOutputToContain('Could not update composer.json with the asset refresh hook.')
            ->expectsOutputToContain('Horizon scaffolding installed successfully.')
            ->assertSuccessful();
    }

    public function test_install_warns_when_composer_hook_cannot_be_updated()
    {
        $hook = Mockery::mock(ComposerAssetHook::class);
        $hook->shouldReceive('ensure')
            ->once()
            ->andReturn(ComposerAssetHookResult::Malformed);

        $this->app->instance(ComposerAssetHook::class, $hook);

        $this->artisan('horizon:install')
            ->expectsOutputToContain('Could not update composer.json with the asset refresh hook.')
            ->expectsOutputToContain('Horizon scaffolding installed successfully.')
            ->assertSuccessful();
    }

    public function test_install_is_silent_when_composer_hook_already_present()
    {
        $hook = Mockery::mock(ComposerAssetHook::class);
        $hook->shouldReceive('ensure')
            ->once()
            ->andReturn(ComposerAssetHookResult::AlreadyPresent);

        $this->app->instance(ComposerAssetHook::class, $hook);

        $this->artisan('horizon:install')
            ->doesntExpectOutputToContain('Added the Horizon asset refresh Composer hook.')
            ->doesntExpectOutputToContain('Could not update composer.json with the asset refresh hook.')
            ->expectsOutputToContain('Horizon scaffolding installed successfully.')
            ->assertSuccessful();
    }
}
