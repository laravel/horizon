<?php

namespace Laravel\Horizon;

use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Horizon\Connectors\RedisConnector;

class HorizonServiceProvider extends ServiceProvider
{
    use EventMap, ServiceBindings;

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $this->registerEvents();
        $this->registerResources();
        $this->registerMacro();
    }

    /**
     * Register the Horizon job events.
     */
    protected function registerEvents()
    {
        $events = $this->app->make(Dispatcher::class);

        foreach ($this->events as $event => $listeners) {
            foreach ($listeners as $listener) {
                $events->listen($event, $listener);
            }
        }
    }

    /**
     * Register the Horizon resources.
     */
    protected function registerResources()
    {
        // config
        $this->mergeConfigFrom(__DIR__ . '/../config/horizon.php', 'horizon');
        $this->publishes([
            __DIR__ . '/../config/horizon.php' => config_path('horizon.php'),
        ], 'horizon-config');

        // views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'horizon');
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/horizon'),
        ], 'horizon-views');

        // resources
        $this->publishes([
            __DIR__ . '/../resources/assets' => resource_path('assets/vendor/horizon'),
        ], 'horizon-assets');
    }

    /**
     * route group namespace workaround.
     */
    protected function registerMacro()
    {
        $this->app['router']->macro('setGroupNamespace', function ($namesapce = null) {
            $lastGroupStack = array_pop($this->groupStack);
            if ($lastGroupStack !== null) {
                array_set($lastGroupStack, 'namespace', $namesapce);
                $this->groupStack[] = $lastGroupStack;
            }

            return $this;
        });
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        if (!defined('HORIZON_PATH')) {
            define('HORIZON_PATH', realpath(__DIR__ . '/../'));
        }

        if ($config = config('horizon.use')) {
            Horizon::use($config);
        }

        $this->registerServices();
        $this->registerCommands();
        $this->registerQueueConnectors();
    }

    /**
     * Register Horizon's services in the container.
     */
    protected function registerServices()
    {
        foreach ($this->serviceBindings as $key => $value) {
            is_numeric($key)
                    ? $this->app->singleton($value)
                    : $this->app->singleton($key, $value);
        }
    }

    /**
     * Register the Horizon Artisan commands.
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\AssetsCommand::class,
                Console\HorizonCommand::class,
                Console\ListCommand::class,
                Console\PurgeCommand::class,
                Console\PauseCommand::class,
                Console\ContinueCommand::class,
                Console\SupervisorCommand::class,
                Console\SupervisorsCommand::class,
                Console\TerminateCommand::class,
                Console\TimeoutCommand::class,
                Console\WorkCommand::class,
            ]);
        }

        $this->commands([Console\SnapshotCommand::class]);
    }

    /**
     * Register the custom queue connectors for Horizon.
     */
    protected function registerQueueConnectors()
    {
        $this->app->resolving(QueueManager::class, function ($manager) {
            $manager->addConnector('redis', function () {
                return new RedisConnector($this->app['redis']);
            });
        });
    }
}
