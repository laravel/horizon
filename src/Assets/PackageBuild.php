<?php

namespace Laravel\Horizon\Assets;

use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

/**
 * Package-owned dist/build paths and manifest resolution for the Inertia dashboard.
 */
class PackageBuild
{
    public const ENTRY = 'resources/js/app.tsx';

    public const FAVICON = 'resources/images/favicon.svg';

    public const MANIFEST = 'manifest.json';

    /**
     * Absolute path to the package dist/build directory, optionally with a relative file.
     */
    public function path(string $relative = ''): string
    {
        $base = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'dist'.DIRECTORY_SEPARATOR.'build';

        if ($relative === '') {
            return $base;
        }

        $safe = $this->normalizeRelativeAssetPath($relative);

        if ($safe === null) {
            throw new RuntimeException('The Horizon package asset path is invalid.');
        }

        return $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $safe);
    }

    /**
     * Absolute path to the package Vite manifest.
     */
    public function manifestPath(): string
    {
        return $this->path(self::MANIFEST);
    }

    /**
     * Absolute path to the package-built JS entry (from dist/build/manifest.json).
     */
    public function script(): string
    {
        return $this->entry()['script'];
    }

    /**
     * Absolute paths to package-built CSS files for the Inertia entry.
     *
     * @return list<string>
     */
    public function styles(): array
    {
        return $this->entry()['styles'];
    }

    /**
     * Resolve the package dist/build manifest entry for the Inertia dashboard.
     *
     * Manifest-referenced paths are rejected when absolute, drive-letter, empty,
     * or containing "." / ".." segments (same rules as {@see AssetsPublisher}).
     *
     * @return array{script: string, styles: list<string>, assets: list<string>}
     */
    public function entry(): array
    {
        $manifestPath = $this->manifestPath();

        if (! is_file($manifestPath)) {
            throw new RuntimeException('Unable to load the Horizon package asset manifest.');
        }

        try {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The Horizon package asset manifest is invalid.', 0, $exception);
        }

        $entry = is_array($manifest) ? ($manifest[self::ENTRY] ?? null) : null;

        if (! is_array($entry) || ! is_string($entry['file'] ?? null) || $entry['file'] === '') {
            throw new RuntimeException('The Horizon package asset manifest has no application entry.');
        }

        $scriptRelative = $this->normalizeRelativeAssetPath($entry['file']);

        if ($scriptRelative === null) {
            throw new RuntimeException('The Horizon package asset manifest has an unsafe script path.');
        }

        $script = $this->path($scriptRelative);

        if (! is_file($script)) {
            throw new RuntimeException('Unable to load the Horizon dashboard JavaScript.');
        }

        $stylePaths = $entry['css'] ?? [];

        if (! is_array($stylePaths)) {
            throw new RuntimeException('The Horizon package asset manifest has invalid styles.');
        }

        $styles = [];

        foreach ($stylePaths as $css) {
            if (! is_string($css) || $css === '') {
                throw new RuntimeException('The Horizon package asset manifest has invalid styles.');
            }

            $styleRelative = $this->normalizeRelativeAssetPath($css);

            if ($styleRelative === null) {
                throw new RuntimeException('The Horizon package asset manifest has an unsafe style path.');
            }

            $stylePath = $this->path($styleRelative);

            if (! is_file($stylePath)) {
                throw new RuntimeException('Unable to load the Horizon dashboard CSS.');
            }

            $styles[] = $stylePath;
        }

        $assetPaths = $entry['assets'] ?? [];

        if (! is_array($assetPaths)) {
            throw new RuntimeException('The Horizon package asset manifest has invalid assets.');
        }

        $assets = [];

        foreach ($assetPaths as $asset) {
            if (! is_string($asset) || $asset === '') {
                throw new RuntimeException('The Horizon package asset manifest has invalid assets.');
            }

            $assetRelative = $this->normalizeRelativeAssetPath($asset);

            if ($assetRelative === null) {
                throw new RuntimeException('The Horizon package asset manifest has an unsafe asset path.');
            }

            $assetPath = $this->path($assetRelative);

            if (! is_file($assetPath)) {
                throw new RuntimeException('Unable to load a Horizon dashboard asset referenced by the manifest.');
            }

            $assets[] = $assetPath;
        }

        return [
            'script' => $script,
            'styles' => $styles,
            'assets' => $assets,
        ];
    }

    /**
     * Whether a build directory has a complete, safe Vite manifest (package or published).
     */
    public function isComplete(string $buildDirectory): bool
    {
        return $this->manifestReferencedFiles($buildDirectory) !== null;
    }

    /**
     * Validate a build directory manifest and return safe relative file paths it references.
     *
     * Requires every entry file, css[], and assets[] path to exist with safe relative form,
     * and every imports/dynamicImports reference to resolve within the same manifest.
     *
     * @return list<string>|null
     */
    public function manifestReferencedFiles(string $buildDirectory): ?array
    {
        $manifestPath = rtrim($buildDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.self::MANIFEST;

        if (! is_file($manifestPath)) {
            return null;
        }

        try {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($manifest) || array_is_list($manifest) || ! array_key_exists(self::ENTRY, $manifest)) {
            return null;
        }

        $assetPaths = [];

        foreach ($manifest as $key => $entry) {
            if (! is_string($key) || ! is_array($entry)) {
                return null;
            }

            $file = $this->existingRelativeFile($buildDirectory, $entry['file'] ?? null);

            if ($file === null) {
                return null;
            }

            $assetPaths[] = $file;

            foreach (['css', 'assets'] as $collection) {
                $paths = $entry[$collection] ?? [];

                if (! is_array($paths)) {
                    return null;
                }

                foreach ($paths as $path) {
                    $publishedPath = $this->existingRelativeFile($buildDirectory, $path);

                    if ($publishedPath === null) {
                        return null;
                    }

                    $assetPaths[] = $publishedPath;
                }
            }

            foreach (['imports', 'dynamicImports'] as $collection) {
                $imports = $entry[$collection] ?? [];

                if (! is_array($imports)) {
                    return null;
                }

                foreach ($imports as $import) {
                    if (! is_string($import) || ! array_key_exists($import, $manifest)) {
                        return null;
                    }
                }
            }
        }

        return array_values(array_unique($assetPaths));
    }

    /**
     * @return string|null Safe relative path that exists under the build directory.
     */
    public function existingRelativeFile(string $buildDirectory, mixed $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $relativePath = $this->normalizeRelativeAssetPath($path);

        if ($relativePath === null) {
            return null;
        }

        $absolute = rtrim($buildDirectory, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        return is_file($absolute) ? $relativePath : null;
    }

    /**
     * Reject absolute paths, drive letters, empty segments, and parent-directory segments.
     */
    public function normalizeRelativeAssetPath(string $path): ?string
    {
        $normalized = str_replace('\\', '/', trim($path));

        if ($normalized === '' || Str::startsWith($normalized, ['/']) || preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
            return null;
        }

        $segments = [];

        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '' || $segment === '.') {
                return null;
            }

            if ($segment === '..') {
                return null;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }
}
