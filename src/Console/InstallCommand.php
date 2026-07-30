<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Horizon\Assets\AssetPath;
use Laravel\Horizon\Assets\AssetsPublisher;
use Laravel\Horizon\Support\ComposerAssetHook;
use Laravel\Horizon\Support\ComposerAssetHookResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'horizon:install')]
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:install
        {--force : Refresh previously published assets}
        {--no-composer-hook : Skip adding the Composer post-autoload-dump asset refresh hook}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Horizon resources';

    /**
     * Execute the console command.
     */
    public function handle(AssetsPublisher $publisher, AssetPath $assetPath, ComposerAssetHook $composerAssetHook): int
    {
        $this->components->info('Installing Horizon resources.');

        collect([
            'Service Provider' => fn () => $this->callSilent('vendor:publish', ['--tag' => 'horizon-provider']) == 0,
            'Configuration' => fn () => $this->callSilent('vendor:publish', ['--tag' => 'horizon-config']) == 0,
        ])->each(fn ($task, $description) => $this->components->task($description, $task));

        $this->registerHorizonServiceProvider();

        try {
            $this->components->task('Dashboard assets', function () use ($publisher, $assetPath) {
                $publisher->publish(
                    destination: $assetPath->absolute(),
                    force: (bool) $this->option('force'),
                );

                return true;
            });
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $this->option('no-composer-hook')) {
            $this->ensureComposerAssetHook($composerAssetHook);
        }

        $this->components->info('Horizon scaffolding installed successfully.');

        return self::SUCCESS;
    }

    /**
     * Register the Horizon service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerHorizonServiceProvider()
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        if (file_exists($this->laravel->bootstrapPath('providers.php'))) {
            ServiceProvider::addProviderToBootstrapFile("{$namespace}\\Providers\\HorizonServiceProvider");
        } else {
            $appConfig = file_get_contents(config_path('app.php'));

            if (Str::contains($appConfig, $namespace.'\\Providers\\HorizonServiceProvider::class')) {
                return;
            }

            file_put_contents(config_path('app.php'), str_replace(
                "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL,
                "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL."        {$namespace}\Providers\HorizonServiceProvider::class,".PHP_EOL,
                $appConfig
            ));
        }

        file_put_contents(app_path('Providers/HorizonServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/HorizonServiceProvider.php'))
        ));
    }

    /**
     * Idempotently append the horizon:assets Composer hook.
     */
    protected function ensureComposerAssetHook(ComposerAssetHook $composerAssetHook): void
    {
        $result = $composerAssetHook->ensure(base_path('composer.json'));

        match ($result) {
            ComposerAssetHookResult::Added => $this->components->info(
                'Added the Horizon asset refresh Composer hook.',
            ),
            ComposerAssetHookResult::AlreadyPresent => null,
            ComposerAssetHookResult::Missing,
            ComposerAssetHookResult::Malformed,
            ComposerAssetHookResult::Failed => $this->components->warn(
                'Could not update composer.json with the asset refresh hook. Run `php artisan horizon:assets` after Composer installs, or add `@php artisan horizon:assets --ansi` to scripts.post-autoload-dump manually.',
            ),
        };
    }
}
