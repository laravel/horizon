<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Laravel\Horizon\Assets\PackageBuild;
use Laravel\Horizon\Tests\UnitTest;
use RuntimeException;

class PackageBuildTest extends UnitTest
{
    private PackageBuild $packageBuild;

    private string $workspace;

    private Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->packageBuild = new PackageBuild;
        $this->files = new Filesystem;
        $this->workspace = sys_get_temp_dir().'/horizon-package-build-'.bin2hex(random_bytes(8));
        $this->files->ensureDirectoryExists($this->workspace);
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->workspace);

        parent::tearDown();
    }

    public function test_normalize_relative_asset_path_accepts_safe_nested_paths()
    {
        $this->assertSame(
            'assets/inertia-hash.js',
            $this->packageBuild->normalizeRelativeAssetPath('assets/inertia-hash.js'),
        );
        $this->assertSame(
            'assets/inertia-hash.js',
            $this->packageBuild->normalizeRelativeAssetPath('assets\\inertia-hash.js'),
        );
    }

    public function test_normalize_relative_asset_path_rejects_unsafe_paths()
    {
        $this->assertNull($this->packageBuild->normalizeRelativeAssetPath('/absolute.js'));
        $this->assertNull($this->packageBuild->normalizeRelativeAssetPath('C:/windows.js'));
        $this->assertNull($this->packageBuild->normalizeRelativeAssetPath('../escape.js'));
        $this->assertNull($this->packageBuild->normalizeRelativeAssetPath('assets/./evil.js'));
        $this->assertNull($this->packageBuild->normalizeRelativeAssetPath('assets//double.js'));
        $this->assertNull($this->packageBuild->normalizeRelativeAssetPath(''));
        $this->assertNull($this->packageBuild->normalizeRelativeAssetPath('   '));
    }

    public function test_path_rejects_unsafe_relative_segments()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The Horizon package asset path is invalid.');

        $this->packageBuild->path('../evil.js');
    }

    public function test_entry_resolves_package_manifest_when_present()
    {
        if (! is_file($this->packageBuild->manifestPath())) {
            $this->markTestSkipped('Package dist/build manifest is not available.');
        }

        $entry = $this->packageBuild->entry();

        $this->assertFileExists($entry['script']);
        $this->assertNotEmpty($entry['styles']);
        $this->assertFileExists($entry['styles'][0]);
        $this->assertStringStartsWith($this->packageBuild->path().DIRECTORY_SEPARATOR, $entry['script']);
        $this->assertNotEmpty($entry['assets']);
        $this->assertTrue(
            !empty(array_filter($entry['assets'], fn (string $path): bool => str_ends_with($path, '.woff2'))),
        );
        foreach ($entry['assets'] as $asset) {
            $this->assertFileExists($asset);
        }

        $css = (string) file_get_contents($entry['styles'][0]);
        $this->assertMatchesRegularExpression('#url\(\./[^)]+\.woff2\)#', $css);
        $this->assertStringNotContainsString('data:font', $css);
        $this->assertStringNotContainsString('/build/assets/', $css);
        $this->assertDoesNotMatchRegularExpression('#url\(/build/[^)]+\.woff2\)#', $css);
    }

    public function test_is_complete_rejects_css_and_assets_when_not_arrays()
    {
        $this->writeManifestBuild([
            'resources/js/app.tsx' => [
                'file' => 'assets/app.js',
                'css' => 'assets/app.css',
                'assets' => 'assets/font.woff2',
            ],
        ], files: ['assets/app.js' => 'js']);

        $this->assertFalse($this->packageBuild->isComplete($this->workspace));
        $this->assertNull($this->packageBuild->manifestReferencedFiles($this->workspace));
    }

    public function test_is_complete_rejects_missing_import_targets()
    {
        $this->writeManifestBuild([
            'resources/js/app.tsx' => [
                'file' => 'assets/app.js',
                'css' => ['assets/app.css'],
                'assets' => [],
                'imports' => ['resources/js/missing-chunk.js'],
            ],
        ], files: [
            'assets/app.js' => 'js',
            'assets/app.css' => 'css',
        ]);

        $this->assertFalse($this->packageBuild->isComplete($this->workspace));
    }

    public function test_is_complete_rejects_non_array_dynamic_imports()
    {
        $this->writeManifestBuild([
            'resources/js/app.tsx' => [
                'file' => 'assets/app.js',
                'css' => ['assets/app.css'],
                'assets' => [],
                'dynamicImports' => 'resources/js/chunk.js',
            ],
        ], files: [
            'assets/app.js' => 'js',
            'assets/app.css' => 'css',
        ]);

        $this->assertFalse($this->packageBuild->isComplete($this->workspace));
    }

    public function test_is_complete_rejects_missing_referenced_files()
    {
        $this->writeManifestBuild([
            'resources/js/app.tsx' => [
                'file' => 'assets/app.js',
                'css' => ['assets/missing.css'],
                'assets' => ['assets/font.woff2'],
            ],
        ], files: [
            'assets/app.js' => 'js',
            'assets/font.woff2' => 'font',
        ]);

        $this->assertFalse($this->packageBuild->isComplete($this->workspace));
    }

    public function test_is_complete_accepts_valid_build_with_imports()
    {
        $this->writeManifestBuild([
            'resources/js/app.tsx' => [
                'file' => 'assets/app.js',
                'css' => ['assets/app.css'],
                'assets' => ['assets/font.woff2'],
                'imports' => ['_shared.js'],
                'dynamicImports' => [],
            ],
            '_shared.js' => [
                'file' => 'assets/shared.js',
                'css' => [],
                'assets' => [],
            ],
        ], files: [
            'assets/app.js' => 'js',
            'assets/app.css' => 'css',
            'assets/font.woff2' => 'font',
            'assets/shared.js' => 'shared',
        ]);

        $this->assertTrue($this->packageBuild->isComplete($this->workspace));
        $paths = $this->packageBuild->manifestReferencedFiles($this->workspace);
        $this->assertContains('assets/app.js', $paths);
        $this->assertContains('assets/font.woff2', $paths);
        $this->assertContains('assets/shared.js', $paths);
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, string>  $files
     */
    private function writeManifestBuild(array $manifest, array $files): void
    {
        $this->files->ensureDirectoryExists($this->workspace.'/assets');
        $this->files->put(
            $this->workspace.'/manifest.json',
            json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
        );

        foreach ($files as $relative => $contents) {
            $path = $this->workspace.'/'.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->put($path, $contents);
        }
    }
}
