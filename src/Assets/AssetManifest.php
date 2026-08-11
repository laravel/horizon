<?php

declare(strict_types=1);

namespace Laravel\Horizon\Assets;

use Illuminate\Foundation\Vite;
use Illuminate\Foundation\ViteException;
use Illuminate\Foundation\ViteManifestNotFoundException;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use RuntimeException;
use Throwable;

/**
 * Package-scoped Vite renderer for Horizon's published (or dev-server) assets.
 *
 * Never reads or mutates the consumer application's public/hot file or global
 * Vite configuration. When published assets are missing, incomplete, or stale
 * relative to the package-shipped build, republishes via AssetsPublisher so the
 * Blade entry does not crash or serve an outdated dashboard after upgrades.
 */
final class AssetManifest
{
    private const ENTRY = 'resources/js/app.tsx';

    private const FAVICON = 'resources/images/favicon.svg';

    /**
     * Whether this instance has already ensured published assets for the request.
     */
    private bool $ensured = false;

    public function __construct(
        private readonly AssetPath $assetPath,
        private readonly PackageBuild $packageBuild,
        private readonly AssetsPublisher $publisher,
    ) {
    }

    public function tags(): HtmlString
    {
        if ($devServer = $this->devServer()) {
            return $this->developmentTags($devServer);
        }

        $this->ensurePublishedAssets();

        try {
            return ($this->vite())(self::ENTRY);
        } catch (ViteException $exception) {
            throw $this->missingAssetsException($exception);
        }
    }

    public function favicon(): string
    {
        if ($devServer = $this->devServer()) {
            return $devServer.'/'.self::FAVICON;
        }

        $this->ensurePublishedAssets();

        try {
            return $this->vite()->asset(self::FAVICON);
        } catch (ViteException $exception) {
            throw $this->missingAssetsException($exception);
        }
    }

    public function version(): string
    {
        $this->ensurePublishedAssets();

        $hash = $this->vite()->manifestHash();

        if (! is_string($hash) || $hash === '') {
            throw new RuntimeException(
                'Horizon assets are not published. Run `php artisan horizon:install` or `php artisan horizon:assets`.',
            );
        }

        return $hash;
    }

    /**
     * Ensure the published build matches the package-shipped build.
     *
     * Hot path: complete destination + matching manifest hash is treated as
     * current (Vite filenames are content-hashed; isComplete already proves
     * referenced files exist). Publisher runs only for missing, incomplete, or
     * manifest-mismatched trees. Failures to write surface as an actionable
     * exception rather than serving outdated assets. A per-instance flag skips
     * repeated work when tags/favicon/version run in one render.
     */
    private function ensurePublishedAssets(): void
    {
        if ($this->ensured) {
            return;
        }

        try {
            $destination = $this->assetPath->absolute();
        } catch (Throwable) {
            return;
        }

        $packagePath = $this->packageBuild->path();

        if (! $this->packageBuild->isComplete($packagePath)) {
            return;
        }

        if ($this->publishedMatchesPackageManifest($destination, $packagePath)) {
            $this->ensured = true;

            return;
        }

        try {
            $this->publisher->publish(
                destination: $destination,
                force: false,
            );
        } catch (Throwable $exception) {
            if ($this->publishedMatchesPackageManifest($destination, $packagePath)) {
                $this->ensured = true;

                return;
            }

            throw new RuntimeException(
                'Horizon assets are not published. Run `php artisan horizon:install` or `php artisan horizon:assets`.',
                previous: $exception,
            );
        }

        $this->ensured = true;
    }

    /**
     * Cheap currency check: destination complete and package/published manifests match.
     */
    private function publishedMatchesPackageManifest(string $destination, string $packagePath): bool
    {
        if (! $this->packageBuild->isComplete($destination)) {
            return false;
        }

        $packageManifest = $packagePath.DIRECTORY_SEPARATOR.PackageBuild::MANIFEST;
        $publishedManifest = $destination.DIRECTORY_SEPARATOR.PackageBuild::MANIFEST;

        if (! is_file($packageManifest) || ! is_file($publishedManifest)) {
            return false;
        }

        $packageHash = hash_file('sha256', $packageManifest);
        $publishedHash = hash_file('sha256', $publishedManifest);

        return is_string($packageHash)
            && is_string($publishedHash)
            && hash_equals($packageHash, $publishedHash);
    }

    /**
     * Fresh Vite instance configured only for Horizon's published build.
     */
    private function vite(): Vite
    {
        $vite = (new Vite)
            ->useBuildDirectory($this->assetPath->relative())
            ->useHotFile($this->packageHotFile())
            ->createAssetPathsUsing(fn (string $path, $secure = null): string => $this->assetUrl($path))
            ->useScriptTagAttributes([
                'data-horizon-inertia' => true,
            ])
            ->useStyleTagAttributes([
                'data-horizon-inertia' => true,
            ]);

        $nonce = app(Vite::class)->cspNonce();

        if (is_string($nonce) && $nonce !== '') {
            $vite->useCspNonce($nonce);
        }

        return $vite;
    }

    private function packageHotFile(): string
    {
        return $this->assetPath->absolute().DIRECTORY_SEPARATOR.'hot';
    }

    /**
     * Same-origin root-relative path, optionally prefixed with horizon.proxy_path.
     */
    private function assetUrl(string $path): string
    {
        $asset = '/'.ltrim(str_replace('\\', '/', $path), '/');
        $proxy = trim((string) config('horizon.proxy_path', ''), '/');

        if ($proxy === '') {
            return $asset;
        }

        return '/'.$proxy.$asset;
    }

    private function developmentTags(string $devServer): HtmlString
    {
        $nonce = app(Vite::class)->cspNonce();
        $nonceAttribute = is_string($nonce) && $nonce !== ''
            ? ' nonce="'.e($nonce).'"'
            : '';
        $devServerAttribute = e($devServer);
        $refreshRuntime = Js::from($devServer.'/@react-refresh');
        $entry = e($devServer.'/'.self::ENTRY);

        return new HtmlString(
            <<<HTML
                <script type="module" src="{$devServerAttribute}/@vite/client"{$nonceAttribute}></script>
                <script type="module"{$nonceAttribute}>
                    import RefreshRuntime from {$refreshRuntime};

                    RefreshRuntime.injectIntoGlobalHook(window);
                    window.\$RefreshReg\$ = () => {};
                    window.\$RefreshSig\$ = () => (type) => type;
                    window.__vite_plugin_react_preamble_installed__ = true;
                </script>
                <script type="module" src="{$entry}"{$nonceAttribute}></script>
                HTML
        );
    }

    private function devServer(): ?string
    {
        return ($url = config('horizon.vite_dev_server')) ? rtrim($url, '/') : null;
    }

    private function missingAssetsException(ViteException $exception): RuntimeException
    {
        if (
            $exception instanceof ViteManifestNotFoundException
            || str_contains($exception->getMessage(), 'Vite manifest not found')
        ) {
            return new RuntimeException(
                'Horizon assets are not published. Run `php artisan horizon:install` or `php artisan horizon:assets`.',
                previous: $exception,
            );
        }

        return new RuntimeException(
            'The published Horizon asset manifest is invalid. Run `php artisan horizon:assets --force`.',
            previous: $exception,
        );
    }
}
