<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Assets\AssetPath;
use Laravel\Horizon\Assets\PackageBuild;
use Laravel\Horizon\Tests\IntegrationTest;

class AssetsCommandTest extends IntegrationTest
{
    public function test_horizon_assets_publishes_build_into_public_vendor_path()
    {
        $destination = app(AssetPath::class)->absolute();
        $packageBuild = app(PackageBuild::class);
        $entry = $packageBuild->entry();
        $scriptRelative = $this->relativeToBuild($packageBuild, $entry['script']);
        $styleRelative = $this->relativeToBuild($packageBuild, $entry['styles'][0]);

        $this->artisan('horizon:assets', ['--force' => true])
            ->assertSuccessful();

        $this->assertFileExists($destination.'/manifest.json');
        $this->assertFileExists($destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $scriptRelative));
        $this->assertFileExists($destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $styleRelative));

        $this->assertNotEmpty($entry['assets']);
        foreach ($entry['assets'] as $assetAbsolute) {
            $relative = $this->relativeToBuild($packageBuild, $assetAbsolute);
            $this->assertFileExists($destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative));
            $this->assertStringEndsWith('.woff2', $relative);
        }

        $this->assertSame(
            hash_file('sha256', $packageBuild->manifestPath()),
            hash_file('sha256', $destination.'/manifest.json'),
        );

        $publishedManifest = json_decode(
            (string) file_get_contents($destination.'/manifest.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $this->assertArrayHasKey('resources/js/app.tsx', $publishedManifest);
        $this->assertNotEmpty($publishedManifest['resources/js/app.tsx']['assets'] ?? []);
    }

    public function test_horizon_assets_is_idempotent()
    {
        $destination = app(AssetPath::class)->absolute();
        $packageBuild = app(PackageBuild::class);
        $scriptRelative = $this->relativeToBuild($packageBuild, $packageBuild->script());

        $this->artisan('horizon:assets', ['--force' => true])->assertSuccessful();
        $mtime = filemtime($destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $scriptRelative));

        sleep(1);

        $this->artisan('horizon:assets')->assertSuccessful();
        $this->assertSame(
            $mtime,
            filemtime($destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $scriptRelative)),
        );
    }

    private function relativeToBuild(PackageBuild $packageBuild, string $absolute): string
    {
        $base = rtrim(str_replace('\\', '/', $packageBuild->path()), '/').'/';
        $path = str_replace('\\', '/', $absolute);

        $this->assertStringStartsWith($base, $path);

        return substr($path, strlen($base));
    }
}
