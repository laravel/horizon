<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Assets\AssetPath;
use Laravel\Horizon\Tests\IntegrationTest;

class PublishCommandTest extends IntegrationTest
{
    public function test_horizon_publish_warns_and_still_publishes_assets()
    {
        $destination = app(AssetPath::class)->absolute();

        $this->artisan('horizon:publish', ['--force' => true])
            ->expectsOutputToContain('horizon:publish is deprecated; use horizon:assets or horizon:install.')
            ->expectsOutputToContain('Horizon assets are ready.')
            ->assertSuccessful();

        $this->assertFileExists($destination.'/manifest.json');
        $this->assertDirectoryExists($destination.'/assets');
    }
}
