<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Horizon\Exceptions\UnsupportedDriverException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'horizon:install')]
class InstallCommand extends Command
{
    /**
     * The supported Horizon drivers.
     */
    protected const SUPPORTED_DRIVERS = ['redis', 'database'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:install
                            {--driver= : The driver Horizon should use (redis or database)}
                            {--no-migrate : Skip running migrations for the database driver}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Horizon resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $driver = $this->resolveDriver();

        $this->components->info("Installing Horizon resources for the [{$driver}] driver.");

        $tasks = [
            'Service Provider' => fn () => $this->callSilent('vendor:publish', ['--tag' => 'horizon-provider']) == 0,
            'Configuration' => fn () => $this->callSilent('vendor:publish', ['--tag' => 'horizon-config']) == 0,
        ];

        if ($driver === 'database') {
            $tasks['Database Migrations'] = fn () => $this->callSilent('vendor:publish', ['--tag' => 'horizon-migrations']) == 0;

            if (! $this->option('no-migrate')) {
                $tasks['Running Migrations'] = fn () => $this->callSilent('migrate', ['--force' => true]) == 0;
            }
        }

        collect($tasks)->each(fn ($task, $description) => $this->components->task($description, $task));

        $this->applyDriverToPublishedConfig($driver);

        $this->registerHorizonServiceProvider();

        $this->components->info('Horizon scaffolding installed successfully.');
    }

    /**
     * Resolve which driver the user wants to install.
     */
    protected function resolveDriver(): string
    {
        $driver = $this->option('driver');

        if ($driver === null) {
            $driver = $this->choice(
                'Which driver would you like Horizon to use?',
                self::SUPPORTED_DRIVERS,
                'redis'
            );
        }

        if (! in_array($driver, self::SUPPORTED_DRIVERS, true)) {
            throw new UnsupportedDriverException(
                "Horizon does not support the [{$driver}] driver."
            );
        }

        return $driver;
    }

    /**
     * Update the published config file so the default driver matches the selection.
     */
    protected function applyDriverToPublishedConfig(string $driver): void
    {
        if ($driver === 'redis') {
            return;
        }

        $path = config_path('horizon.php');

        if (! file_exists($path)) {
            return;
        }

        $contents = file_get_contents($path);

        $marker = "'driver' => env('HORIZON_DRIVER', 'redis'),";

        $updated = str_replace(
            $marker,
            "'driver' => env('HORIZON_DRIVER', '{$driver}'),",
            $contents
        );

        if ($updated === $contents) {
            throw new \RuntimeException(sprintf(
                'Failed to update Horizon driver to [%s] — expected marker line "%s" not found in config/horizon.php.',
                $driver,
                $marker
            ));
        }

        $updated = str_replace(
            ["'connection' => 'redis',", "'redis:default' => 60,"],
            ["'connection' => '{$driver}',", "'{$driver}:default' => 60,"],
            $updated
        );

        file_put_contents($path, $updated);
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
}
