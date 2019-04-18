<?php

namespace Laravel\Horizon\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;

class InstallCommand extends Command
{
    use DetectsApplicationNamespace;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:install';

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
        $this->comment('Publishing Horizon Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'horizon-provider']);

        $this->comment('Publishing Horizon Assets...');
        $this->callSilent('vendor:publish', ['--tag' => 'horizon-assets']);

        $this->comment('Publishing Horizon Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'horizon-config']);

        $this->registerHorizonServiceProvider();

        $this->info('Horizon scaffolding installed successfully.');
    }

    /**
     * Register the Horizon service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerHorizonServiceProvider()
    {
        $namespace = Str::replaceLast('\\', '', $this->getAppNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace.'\\Providers\\HorizonServiceProvider::class')) {
            return;
        }

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL,
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL."        {$namespace}\Providers\HorizonServiceProvider::class,".PHP_EOL,
            $appConfig
        ));

        file_put_contents(app_path('Providers/HorizonServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/HorizonServiceProvider.php'))
        ));
    }
}
