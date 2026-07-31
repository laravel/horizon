<?php

namespace Laravel\Horizon\Assets;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

class AssetsPublisher
{
    private const ABANDONED_STAGING_AFTER_SECONDS = 3600;

    public function __construct(
        protected Filesystem $filesystem,
        protected PackageBuild $packageBuild,
    ) {
    }

    /**
     * Absolute path to the package-built assets that ship with Horizon.
     */
    public function packageBuildPath(): string
    {
        return $this->packageBuild->path();
    }

    /**
     * Publish the package build into the consumer public directory.
     *
     * @throws \RuntimeException
     */
    public function publish(string $destination, bool $force, ?string $source = null): void
    {
        $source ??= $this->packageBuildPath();

        $this->filesystem->ensureDirectoryExists(dirname($destination));
        $this->removeAbandonedStagingDirectories($destination);

        if (! $force && $this->hasCurrentPublishedAssets($source, $destination)) {
            return;
        }

        $stagingDirectory = dirname($destination).DIRECTORY_SEPARATOR.'.'.basename($destination).'-'.Str::random(20).'.tmp';

        try {
            if (! $this->filesystem->copyDirectory($source, $stagingDirectory)) {
                throw new RuntimeException('Unable to stage Horizon assets.');
            }

            if (! $this->hasCompletePublishedAssets($stagingDirectory)) {
                throw new RuntimeException('Unable to stage a complete Horizon asset build.');
            }

            if (! $this->filesystem->isDirectory($destination)) {
                if (! $this->filesystem->moveDirectory($stagingDirectory, $destination)) {
                    throw new RuntimeException("Unable to publish {$destination}.");
                }

                return;
            }

            $this->mergeStagedAssets($stagingDirectory, $destination);
        } finally {
            if ($this->filesystem->isDirectory($stagingDirectory)) {
                $this->filesystem->deleteDirectory($stagingDirectory);
            }
        }
    }

    /**
     * Whether the destination is a complete published build matching the package source.
     */
    public function isCurrent(string $destination, ?string $source = null): bool
    {
        return $this->hasCurrentPublishedAssets($source ?? $this->packageBuildPath(), $destination);
    }

    /**
     * Determine whether the destination already matches the package build.
     */
    protected function hasCurrentPublishedAssets(string $source, string $destination): bool
    {
        if (! $this->hasCompletePublishedAssets($destination)) {
            return false;
        }

        if (! $this->publishedFilesMatch(
            $source.DIRECTORY_SEPARATOR.PackageBuild::MANIFEST,
            $destination.DIRECTORY_SEPARATOR.PackageBuild::MANIFEST,
        )) {
            return false;
        }

        foreach ($this->filesystem->allFiles($source) as $sourceFile) {
            $destinationFile = $destination.DIRECTORY_SEPARATOR.$sourceFile->getRelativePathname();

            if (! $this->publishedFilesMatch($sourceFile->getPathname(), $destinationFile)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the destination contains a complete, valid build.
     */
    protected function hasCompletePublishedAssets(string $destination): bool
    {
        if (! $this->filesystem->isDirectory($destination)) {
            return false;
        }

        return $this->manifestAssetPaths($destination) !== null;
    }

    /**
     * Compare two files by SHA-256.
     */
    protected function publishedFilesMatch(string $source, string $destination): bool
    {
        return $this->filesystem->isFile($destination)
            && $this->filesystem->hash($source, 'sha256') === $this->filesystem->hash($destination, 'sha256');
    }

    /**
     * Validate the manifest and return relative asset paths, or null when invalid.
     *
     * @return list<string>|null
     */
    protected function manifestAssetPaths(string $destination): ?array
    {
        return $this->packageBuild->manifestReferencedFiles($destination);
    }

    /**
     * Copy staged files into the destination, writing the manifest last, then prune.
     */
    protected function mergeStagedAssets(string $stagingDirectory, string $destination): void
    {
        $stagedPaths = [];

        foreach ($this->filesystem->allFiles($stagingDirectory, hidden: true) as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $stagedPaths[] = $relativePath;

            if ($relativePath === PackageBuild::MANIFEST) {
                continue;
            }

            $this->publishAssetFile(
                $file->getPathname(),
                $destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath),
            );
        }

        $this->publishAssetFile(
            $stagingDirectory.DIRECTORY_SEPARATOR.PackageBuild::MANIFEST,
            $destination.DIRECTORY_SEPARATOR.PackageBuild::MANIFEST,
        );

        // Retain only the newly staged build (including the updated manifest).
        // Previous manifest paths that are absent from the new build are pruned.
        $this->pruneSupersededAssets($destination, $stagedPaths);
    }

    /**
     * Delete destination files that are not part of the new staged build.
     *
     * @param  list<string>  $retainedPaths  Relative paths from the staged build (includes manifest.json).
     */
    protected function pruneSupersededAssets(string $destination, array $retainedPaths): void
    {
        $retainedPaths = array_fill_keys($retainedPaths, true);

        foreach ($this->filesystem->allFiles($destination, hidden: true) as $asset) {
            $relativePath = str_replace('\\', '/', $asset->getRelativePathname());

            if (! isset($retainedPaths[$relativePath])) {
                $this->filesystem->delete($asset->getPathname());
            }
        }
    }

    /**
     * Remove abandoned staging directories older than the retention window.
     */
    protected function removeAbandonedStagingDirectories(string $destination): void
    {
        $stagingDirectories = $this->filesystem->glob(
            dirname($destination).DIRECTORY_SEPARATOR.'.'.basename($destination).'-*.tmp',
        ) ?: [];
        $abandonedBefore = time() - self::ABANDONED_STAGING_AFTER_SECONDS;

        foreach ($stagingDirectories as $stagingDirectory) {
            if (
                ! $this->filesystem->isDirectory($stagingDirectory)
                || $this->filesystem->lastModified($stagingDirectory) > $abandonedBefore
            ) {
                continue;
            }

            $this->filesystem->deleteDirectory($stagingDirectory);
        }
    }

    /**
     * Atomically copy a single asset file into place.
     *
     * @throws \RuntimeException
     */
    protected function publishAssetFile(string $source, string $destination): void
    {
        $this->filesystem->ensureDirectoryExists(dirname($destination));
        $temporaryDestination = $destination.'.'.Str::random(20).'.tmp';

        try {
            if (! $this->filesystem->copy($source, $temporaryDestination)) {
                throw new RuntimeException("Unable to publish Horizon asset {$destination}.");
            }

            if (! $this->filesystem->move($temporaryDestination, $destination)) {
                throw new RuntimeException("Unable to publish Horizon asset {$destination}.");
            }
        } finally {
            if ($this->filesystem->exists($temporaryDestination)) {
                $this->filesystem->delete($temporaryDestination);
            }
        }
    }
}
