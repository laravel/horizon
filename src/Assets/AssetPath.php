<?php

namespace Laravel\Horizon\Assets;

use Illuminate\Contracts\Foundation\Application;
use RuntimeException;

class AssetPath
{
    public const RELATIVE_PATH = 'vendor/horizon/build';

    public function __construct(
        protected Application $application,
    ) {
    }

    /**
     * Relative path under the public directory.
     */
    public function relative(): string
    {
        return self::RELATIVE_PATH;
    }

    /**
     * Absolute filesystem path for published Horizon assets (within public/).
     *
     * @throws \RuntimeException
     */
    public function absolute(): string
    {
        $relativePath = $this->relative();
        $publicPath = realpath($this->application->publicPath());

        if (! is_string($publicPath)) {
            throw new RuntimeException('The public directory could not be resolved for Horizon assets.');
        }

        $currentPath = $publicPath;

        foreach (explode('/', $relativePath) as $segment) {
            $currentPath .= DIRECTORY_SEPARATOR.$segment;

            if (! file_exists($currentPath) && ! is_link($currentPath)) {
                continue;
            }

            $resolvedPath = realpath($currentPath);

            if (
                ! is_string($resolvedPath)
                || $resolvedPath === $publicPath
                || ! str_starts_with($resolvedPath, $publicPath.DIRECTORY_SEPARATOR)
            ) {
                throw new RuntimeException('The Horizon asset path must resolve within the public directory.');
            }
        }

        return $publicPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    /**
     * Absolute path to the published Vite manifest.
     */
    public function manifest(): string
    {
        return $this->absolute().DIRECTORY_SEPARATOR.'manifest.json';
    }
}
