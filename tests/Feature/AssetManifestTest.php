<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Bus\BatchRepository;
use Illuminate\Foundation\Vite;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laravel\Horizon\Assets\AssetManifest;
use Laravel\Horizon\Assets\AssetPath;
use Laravel\Horizon\Assets\AssetsPublisher;
use Laravel\Horizon\Assets\PackageBuild;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Tests\ControllerTest;
use Mockery;
use ReflectionProperty;
use RuntimeException;

class AssetManifestTest extends ControllerTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // Vite caches parsed manifests in Illuminate\Foundation\Vite::$manifests for
        // the process lifetime; clear it so tests do not share an earlier load.
        $this->flushViteManifestCache();
    }

    public function test_published_tags_ignore_consumer_public_hot_file()
    {
        $hotFile = public_path('hot');
        file_put_contents($hotFile, 'http://localhost:5173');

        try {
            $this->assertTrue($this->app->make(Vite::class)->isRunningHot());

            $tags = app(AssetManifest::class)->tags()->toHtml();

            $this->assertStringContainsString('/vendor/horizon/build/', $tags);
            $this->assertStringNotContainsString('http://localhost:5173', $tags);
            $this->assertStringNotContainsString('@vite/client', $tags);
            $this->assertStringContainsString('data-horizon-inertia', $tags);
        } finally {
            @unlink($hotFile);
        }
    }

    public function test_published_tags_emit_css_js_with_data_attributes_without_window_horizon()
    {
        $tags = app(AssetManifest::class)->tags()->toHtml();

        $this->assertMatchesRegularExpression('#/vendor/horizon/build/[^"\']+\.css#', $tags);
        $this->assertMatchesRegularExpression('#/vendor/horizon/build/[^"\']+\.js#', $tags);
        $this->assertStringContainsString('data-horizon-inertia', $tags);
        $this->assertStringNotContainsString('http://', $tags);
        $this->assertStringNotContainsString('window.Horizon', $tags);
        $this->assertSame(1, substr_count($tags, 'type="module"') + substr_count($tags, "type='module'"));
    }

    public function test_published_tags_and_favicon_include_proxy_path_prefix()
    {
        config(['horizon.proxy_path' => 'gateway']);

        $tags = app(AssetManifest::class)->tags()->toHtml();
        $favicon = app(AssetManifest::class)->favicon();

        $this->assertMatchesRegularExpression('#/gateway/vendor/horizon/build/[^"\']+\.css#', $tags);
        $this->assertMatchesRegularExpression('#/gateway/vendor/horizon/build/[^"\']+\.js#', $tags);
        $this->assertMatchesRegularExpression('#^/gateway/vendor/horizon/build/assets/[^/]+\.svg$#', $favicon);
        $this->assertStringNotContainsString('//vendor', $tags);
        $this->assertStringNotContainsString('"/vendor/horizon/build', $tags);
    }

    public function test_favicon_resolves_hashed_published_url()
    {
        $favicon = app(AssetManifest::class)->favicon();

        $this->assertMatchesRegularExpression('#^/vendor/horizon/build/assets/[^/]+\.svg$#', $favicon);
        $this->assertStringNotContainsString('data:image', $favicon);
    }

    public function test_favicon_uses_dev_server_source_url()
    {
        config(['horizon.vite_dev_server' => 'https://horizon-v2-vite.nmbp']);

        $this->assertSame(
            'https://horizon-v2-vite.nmbp/resources/images/favicon.svg',
            app(AssetManifest::class)->favicon(),
        );
    }

    public function test_dashboard_head_includes_favicon_nonce_and_vite_tags()
    {
        $nonce = Str::random(40);
        Horizon::cspNonce($nonce);

        $batches = Mockery::mock(BatchRepository::class);
        $batches->shouldReceive('get')->with(51, null)->andReturn([]);
        $this->app->instance(BatchRepository::class, $batches);

        $response = $this->actingAs(new Fakes\User)
            ->get(route('horizon.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-horizon-favicon', $html);
        $this->assertStringNotContainsString('window.Horizon', $html);
        $this->assertMatchesRegularExpression(
            '#data-horizon-favicon[\s\S]+/vendor/horizon/build/[^"\']+\.css#',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '#/vendor/horizon/build/[^"\']+\.css[\s\S]+data-horizon-inertia#',
            $html,
        );

        $this->assertStringContainsString("nonce=\"{$nonce}\"", $html);
        $this->assertStringContainsString("<meta name=\"csp-nonce\" content=\"{$nonce}\">", $html);
        $this->assertMatchesRegularExpression(
            '/data-horizon-inertia[^>]*nonce="'.preg_quote($nonce, '/').'"|nonce="'.preg_quote($nonce, '/').'"[^>]*data-horizon-inertia/',
            $html,
        );
        $this->assertSame($nonce, app(Vite::class)->cspNonce());
    }

    public function test_missing_published_assets_are_republished_from_the_package_build()
    {
        config(['horizon.vite_dev_server' => null]);
        $this->destroyPublishedAssets();

        $destination = app(AssetPath::class)->absolute();
        $this->assertFileDoesNotExist($destination.'/manifest.json');

        $tags = app(AssetManifest::class)->tags()->toHtml();

        $this->assertFileExists($destination.'/manifest.json');
        $this->assertTrue(app(PackageBuild::class)->isComplete($destination));
        $this->assertMatchesRegularExpression('#/vendor/horizon/build/[^"\']+\.css#', $tags);
        $this->assertMatchesRegularExpression('#/vendor/horizon/build/[^"\']+\.js#', $tags);
        $this->assertStringContainsString('data-horizon-inertia', $tags);
    }

    public function test_dashboard_renders_when_published_assets_are_absent_after_upgrade()
    {
        config(['horizon.vite_dev_server' => null]);
        $this->destroyPublishedAssets();

        $batches = Mockery::mock(BatchRepository::class);
        $batches->shouldReceive('get')->with(51, null)->andReturn([]);
        $this->app->instance(BatchRepository::class, $batches);

        $response = $this->actingAs(new Fakes\User)->get(route('horizon.index'));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('data-horizon-favicon', $html);
        $this->assertMatchesRegularExpression('#/vendor/horizon/build/[^"\']+\.css#', $html);
        $this->assertMatchesRegularExpression('#/vendor/horizon/build/[^"\']+\.js#', $html);
        $this->assertFileExists(app(AssetPath::class)->manifest());
    }

    public function test_invalid_published_manifest_is_replaced_from_the_package_build()
    {
        config(['horizon.vite_dev_server' => null]);

        $destination = app(AssetPath::class)->absolute();
        $files = new Filesystem;
        $files->ensureDirectoryExists($destination);
        $files->put($destination.'/manifest.json', '{}');
        // Overwriting the on-disk manifest does not clear Vite's in-process cache.
        $this->flushViteManifestCache();

        $tags = app(AssetManifest::class)->tags()->toHtml();

        $this->assertTrue(app(PackageBuild::class)->isComplete($destination));
        $this->assertNotSame('{}', (string) file_get_contents($destination.'/manifest.json'));
        $this->assertMatchesRegularExpression('#/vendor/horizon/build/[^"\']+\.js#', $tags);
    }

    public function test_complete_but_stale_published_assets_are_refreshed_from_the_package_build()
    {
        config(['horizon.vite_dev_server' => null]);

        $packageBuild = app(PackageBuild::class);
        $publisher = app(AssetsPublisher::class);
        $destination = app(AssetPath::class)->absolute();
        $packageManifest = $packageBuild->manifestPath();

        $this->seedCompleteStalePublishedBuild($destination);
        $this->flushViteManifestCache();

        $this->assertTrue($packageBuild->isComplete($destination));
        $this->assertFalse($publisher->isCurrent($destination));
        $this->assertNotSame(
            hash_file('sha256', $packageManifest),
            hash_file('sha256', $destination.'/manifest.json'),
        );

        $staleVersion = md5_file($destination.'/manifest.json');
        $staleManifest = (string) file_get_contents($destination.'/manifest.json');
        $packageScript = basename($packageBuild->entry()['script']);

        $version = app(AssetManifest::class)->version();
        $tags = app(AssetManifest::class)->tags()->toHtml();
        $favicon = app(AssetManifest::class)->favicon();

        $this->assertTrue($publisher->isCurrent($destination));
        $this->assertSame(
            hash_file('sha256', $packageManifest),
            hash_file('sha256', $destination.'/manifest.json'),
        );
        $this->assertNotSame($staleManifest, (string) file_get_contents($destination.'/manifest.json'));
        $this->assertSame(md5_file($destination.'/manifest.json'), $version);
        $this->assertNotSame($staleVersion, $version);
        $this->assertStringContainsString($packageScript, $tags);
        $this->assertStringNotContainsString('app-stale-old.js', $tags);
        $this->assertStringNotContainsString('favicon-stale-old.svg', $favicon);
        $this->assertMatchesRegularExpression('#/vendor/horizon/build/[^"\']+\.js#', $tags);
        $this->assertFileDoesNotExist($destination.'/assets/app-stale-old.js');

        // Matching manifests skip the publisher; a fresh instance still must not rewrite.
        $refreshedMtime = filemtime($destination.'/manifest.json');
        clearstatcache(true, $destination.'/manifest.json');
        sleep(1);
        $this->app->forgetInstance(AssetManifest::class);
        app(AssetManifest::class)->version();
        app(AssetManifest::class)->tags();
        app(AssetManifest::class)->favicon();
        clearstatcache(true, $destination.'/manifest.json');
        $this->assertSame($refreshedMtime, filemtime($destination.'/manifest.json'));
        $this->assertTrue(app(AssetsPublisher::class)->isCurrent($destination));
    }

    public function test_missing_package_and_published_assets_throw_actionable_exception()
    {
        config(['horizon.vite_dev_server' => null]);
        $this->destroyPublishedAssets();

        $packageBuild = Mockery::mock(PackageBuild::class)->makePartial();
        $packageBuild->shouldReceive('isComplete')->andReturn(false);
        $packageBuild->shouldReceive('path')->andReturn('/tmp/horizon-missing-package-build');
        $this->app->instance(PackageBuild::class, $packageBuild);
        $this->app->forgetInstance(AssetsPublisher::class);
        $this->app->forgetInstance(AssetManifest::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Horizon assets are not published');

        app(AssetManifest::class)->tags();
    }

    public function test_development_server_emits_client_refresh_and_entry()
    {
        config(['horizon.vite_dev_server' => 'https://horizon-v2-vite.nmbp']);

        $tags = app(AssetManifest::class)->tags()->toHtml();

        $this->assertStringContainsString('https://horizon-v2-vite.nmbp/@vite/client', $tags);
        $this->assertStringContainsString('@react-refresh', $tags);
        $this->assertStringContainsString(
            'https://horizon-v2-vite.nmbp/resources/js/app.tsx',
            $tags,
        );
        $this->assertStringNotContainsString('window.Horizon', $tags);

        $clientPos = strpos($tags, '@vite/client');
        $entryPos = strpos($tags, 'resources/js/app.tsx');
        $this->assertNotFalse($clientPos);
        $this->assertNotFalse($entryPos);
        $this->assertLessThan($entryPos, $clientPos);
    }

    public function test_package_and_published_css_use_relocatable_relative_font_urls()
    {
        $packageBuild = app(PackageBuild::class);
        $entry = $packageBuild->entry();

        $this->assertNotEmpty($entry['assets']);
        $this->assertNotEmpty($entry['styles']);

        $packageCss = (string) file_get_contents($entry['styles'][0]);
        $this->assertRelocatableFontUrls($packageCss);

        $tags = app(AssetManifest::class)->tags()->toHtml();
        $this->assertMatchesRegularExpression('#/vendor/horizon/build/[^"\']+\.css#', $tags);

        $cssRelative = basename($entry['styles'][0]);
        $publishedCssPath = public_path('vendor/horizon/build/assets/'.$cssRelative);
        $this->assertFileExists($publishedCssPath);

        $publishedCss = (string) file_get_contents($publishedCssPath);
        $this->assertRelocatableFontUrls($publishedCss);

        foreach ($entry['assets'] as $fontAbsolute) {
            $fontName = basename($fontAbsolute);
            $this->assertFileExists(public_path('vendor/horizon/build/assets/'.$fontName));
            $this->assertStringContainsString($fontName, $publishedCss);
            $this->assertStringContainsString($fontName, $packageCss);
        }
    }

    public function test_version_uses_published_manifest_hash()
    {
        $version = app(AssetManifest::class)->version();

        $this->assertNotSame('', $version);
        $this->assertSame(32, strlen($version));
        $this->assertSame($version, app(AssetManifest::class)->version());
        $this->assertSame($version, Horizon::inertiaVersion());
        $this->assertSame(
            md5_file(app(AssetPath::class)->manifest()),
            $version,
        );
    }

    public function test_version_republishes_missing_assets_from_the_package_build()
    {
        config(['horizon.vite_dev_server' => null]);
        $this->destroyPublishedAssets();

        $version = app(AssetManifest::class)->version();

        $this->assertNotSame('', $version);
        $this->assertSame(32, strlen($version));
        $this->assertFileExists(app(AssetPath::class)->manifest());
        $this->assertSame(
            md5_file(app(AssetPath::class)->manifest()),
            $version,
        );
    }

    private function assertRelocatableFontUrls(string $css): void
    {
        $this->assertMatchesRegularExpression('#url\(\./[^)]+\.woff2\)#', $css);
        $this->assertStringNotContainsString('data:font', $css);
        $this->assertStringNotContainsString('data:application/font', $css);
        $this->assertStringNotContainsString('data:application/octet-stream;base64', $css);
        $this->assertStringNotContainsString('/build/assets/', $css);
        $this->assertDoesNotMatchRegularExpression('#url\(/build/[^)]+\.woff2\)#', $css);
        $this->assertDoesNotMatchRegularExpression('#url\(["\']?/build/#', $css);
    }

    /**
     * Delete published assets and drop Illuminate\Foundation\Vite::$manifests.
     *
     * That static cache is keyed by absolute manifest path; removing files alone
     * leaves a previously parsed manifest available for later tests in-process.
     */
    private function destroyPublishedAssets(): void
    {
        (new Filesystem)->deleteDirectory(app(AssetPath::class)->absolute());
        $this->flushViteManifestCache();
    }

    /**
     * Write a complete published tree whose fingerprints differ from the package build.
     */
    private function seedCompleteStalePublishedBuild(string $destination): void
    {
        $files = new Filesystem;
        $files->deleteDirectory($destination);
        $files->ensureDirectoryExists($destination.'/assets');

        $js = 'assets/app-stale-old.js';
        $css = 'assets/app-stale-old.css';
        $font = 'assets/font-stale-old.woff2';
        $favicon = 'assets/favicon-stale-old.svg';

        $files->put($destination.'/'.str_replace('/', DIRECTORY_SEPARATOR, $js), 'stale-js');
        $files->put($destination.'/'.str_replace('/', DIRECTORY_SEPARATOR, $css), 'stale-css');
        $files->put($destination.'/'.str_replace('/', DIRECTORY_SEPARATOR, $font), 'stale-font');
        $files->put($destination.'/'.str_replace('/', DIRECTORY_SEPARATOR, $favicon), '<svg></svg>');

        $files->put($destination.'/manifest.json', json_encode([
            'resources/js/app.tsx' => [
                'file' => $js,
                'src' => 'resources/js/app.tsx',
                'isEntry' => true,
                'css' => [$css],
                'assets' => [$font],
            ],
            'resources/images/favicon.svg' => [
                'file' => $favicon,
                'src' => 'resources/images/favicon.svg',
            ],
            'resources/fonts/source.woff2' => [
                'file' => $font,
                'src' => 'resources/fonts/source.woff2',
            ],
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    private function flushViteManifestCache(): void
    {
        (new ReflectionProperty(Vite::class, 'manifests'))->setValue(null, []);
    }
}
