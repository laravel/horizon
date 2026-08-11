<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Laravel\Horizon\Assets\AssetsPublisher;
use Laravel\Horizon\Assets\PackageBuild;
use Laravel\Horizon\Tests\UnitTest;
use RuntimeException;

class AssetsPublisherTest extends UnitTest
{
    private string $workspace;

    private Filesystem $files;

    private PackageBuild $packageBuild;

    private AssetsPublisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->packageBuild = new PackageBuild;
        $this->publisher = new AssetsPublisher($this->files, $this->packageBuild);
        $this->workspace = sys_get_temp_dir().'/horizon-assets-'.bin2hex(random_bytes(8));
        $this->files->ensureDirectoryExists($this->workspace);
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->workspace);

        parent::tearDown();
    }

    public function test_package_build_path_points_at_dist_build()
    {
        $this->assertSame(
            realpath($this->packageBuild->path()) ?: $this->packageBuild->path(),
            realpath($this->publisher->packageBuildPath()) ?: $this->publisher->packageBuildPath(),
        );
    }

    public function test_publish_copies_manifest_and_hashed_entry_assets()
    {
        $source = $this->fixtureBuild('initial', jsName: 'assets/app-aaa.js', cssName: 'assets/app-aaa.css');
        $destination = $this->workspace.'/vendor/horizon/build';

        $this->publisher->publish($destination, force: false, source: $source);

        $this->assertFileExists($destination.'/manifest.json');
        $this->assertFileExists($destination.'/assets/app-aaa.js');
        $this->assertFileExists($destination.'/assets/app-aaa.css');
        $this->assertFileExists($destination.'/assets/font.woff2');
        $this->assertSame(
            $this->files->hash($source.'/manifest.json', 'sha256'),
            $this->files->hash($destination.'/manifest.json', 'sha256'),
        );
    }

    public function test_publish_is_idempotent_without_force()
    {
        $source = $this->fixtureBuild('initial', jsName: 'assets/app-aaa.js', cssName: 'assets/app-aaa.css');
        $destination = $this->workspace.'/vendor/horizon/build';

        $this->publisher->publish($destination, force: false, source: $source);
        $target = $destination.'/assets/app-aaa.js';
        $past = time() - 10;
        touch($target, $past);
        clearstatcache(true, $target);
        $firstMtime = $this->files->lastModified($target);

        $this->publisher->publish($destination, force: false, source: $source);
        clearstatcache(true, $target);
        $secondMtime = $this->files->lastModified($target);

        $this->assertSame($firstMtime, $secondMtime);
        $this->assertSame($past, $secondMtime);
    }

    public function test_publish_rejects_unsafe_manifest_paths()
    {
        $source = $this->workspace.'/bad-source';
        $this->files->ensureDirectoryExists($source);
        $this->files->put($source.'/manifest.json', json_encode([
            'resources/js/app.tsx' => [
                'file' => '../evil.js',
                'css' => [],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to stage a complete Horizon asset build.');

        $this->publisher->publish($this->workspace.'/out', force: true, source: $source);
    }

    public function test_publish_prunes_previous_manifest_referenced_hashed_filenames()
    {
        $sourceA = $this->fixtureBuild(
            'a',
            jsName: 'assets/app-abc123.js',
            cssName: 'assets/app-abc123.css',
            fontName: 'assets/font-abc123.woff2',
            js: 'old-js',
            css: 'old-css',
            font: 'old-font',
        );
        $destination = $this->workspace.'/vendor/horizon/build';

        $this->publisher->publish($destination, force: true, source: $sourceA);

        $manifestA = json_decode($this->files->get($destination.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('assets/app-abc123.js', $manifestA['resources/js/app.tsx']['file']);
        $this->assertSame(['assets/app-abc123.css'], $manifestA['resources/js/app.tsx']['css']);
        $this->assertSame(['assets/font-abc123.woff2'], $manifestA['resources/js/app.tsx']['assets']);
        $this->assertFileExists($destination.'/assets/app-abc123.js');
        $this->assertFileExists($destination.'/assets/app-abc123.css');
        $this->assertFileExists($destination.'/assets/font-abc123.woff2');

        $sourceB = $this->fixtureBuild(
            'b',
            jsName: 'assets/app-def456.js',
            cssName: 'assets/app-def456.css',
            fontName: 'assets/font-def456.woff2',
            js: 'new-js',
            css: 'new-css',
            font: 'new-font',
        );
        $this->publisher->publish($destination, force: true, source: $sourceB);

        $this->assertFileDoesNotExist($destination.'/assets/app-abc123.js');
        $this->assertFileDoesNotExist($destination.'/assets/app-abc123.css');
        $this->assertFileDoesNotExist($destination.'/assets/font-abc123.woff2');
        $this->assertFileExists($destination.'/assets/app-def456.js');
        $this->assertFileExists($destination.'/assets/app-def456.css');
        $this->assertFileExists($destination.'/assets/font-def456.woff2');
        $this->assertSame('new-js', $this->files->get($destination.'/assets/app-def456.js'));
        $this->assertSame('new-css', $this->files->get($destination.'/assets/app-def456.css'));
        $this->assertSame('new-font', $this->files->get($destination.'/assets/font-def456.woff2'));

        $manifestB = json_decode($this->files->get($destination.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('assets/app-def456.js', $manifestB['resources/js/app.tsx']['file']);
        $this->assertSame(['assets/app-def456.css'], $manifestB['resources/js/app.tsx']['css']);
        $this->assertSame(['assets/font-def456.woff2'], $manifestB['resources/js/app.tsx']['assets']);
        $this->assertStringNotContainsString('abc123', $this->files->get($destination.'/manifest.json'));
    }

    /**
     * @return string Absolute fixture source directory
     */
    private function fixtureBuild(
        string $label,
        string $jsName = 'assets/app.js',
        string $cssName = 'assets/app.css',
        string $fontName = 'assets/font.woff2',
        string $js = 'js',
        string $css = 'css',
        string $font = 'font',
    ): string {
        $source = $this->workspace.'/source-'.$label;
        $this->files->ensureDirectoryExists($source.'/assets');
        $this->files->put($source.'/'.str_replace('/', DIRECTORY_SEPARATOR, $jsName), $js);
        $this->files->put($source.'/'.str_replace('/', DIRECTORY_SEPARATOR, $cssName), $css);
        $this->files->put($source.'/'.str_replace('/', DIRECTORY_SEPARATOR, $fontName), $font);

        $this->files->put($source.'/manifest.json', json_encode([
            'resources/js/app.tsx' => [
                'file' => $jsName,
                'src' => 'resources/js/app.tsx',
                'isEntry' => true,
                'css' => [$cssName],
                'assets' => [$fontName],
            ],
            'resources/fonts/source.woff2' => [
                'file' => $fontName,
                'src' => 'resources/fonts/source.woff2',
            ],
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        return $source;
    }
}
