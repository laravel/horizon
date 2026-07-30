<?php

namespace Laravel\Horizon\Tests;

use Illuminate\Foundation\Vite;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Assets\AssetPath;
use Laravel\Horizon\Assets\AssetsPublisher;
use Laravel\Horizon\Assets\PackageBuild;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\SupervisorCommandString;
use Laravel\Horizon\WorkerCommandString;
use Orchestra\Testbench\TestCase;

abstract class IntegrationTest extends TestCase
{
    /**
     * Setup the test case.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            Redis::connection()->flushdb();
            $this->publishHorizonDashboardAssets();
        });

        $this->beforeApplicationDestroyed(function () {
            Redis::connection()->flushdb();
            WorkerCommandString::reset();
            SupervisorCommandString::reset();
            Horizon::$authUsing = null;
            app(Vite::class)->useCspNonce('');
        });

        parent::setUp();
    }

    /**
     * Run the given assertion callback with a retry loop.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function wait($callback)
    {
        retry(10, $callback, 1000);
    }

    /**
     * Get the total number of recent jobs.
     *
     * @return int
     */
    protected function recentJobs()
    {
        return app(JobRepository::class)->totalRecent();
    }

    /**
     * Get the total number of monitored jobs for a given tag.
     *
     * @param  string  $tag
     * @return int
     */
    protected function monitoredJobs($tag)
    {
        return app(TagRepository::class)->count($tag);
    }

    /**
     * Get the total number of failed jobs.
     *
     * @return int
     */
    protected function failedJobs()
    {
        return app(JobRepository::class)->totalFailed();
    }

    /**
     * Run the next job on the queue.
     *
     * @param  int  $times
     * @return void
     */
    protected function work($times = 1)
    {
        for ($i = 0; $i < $times; $i++) {
            $this->worker()->runNextJob(
                'redis', 'default', $this->workerOptions()
            );
        }
    }

    /**
     * Get the queue worker instance.
     *
     * @return \Illuminate\Queue\Worker
     */
    protected function worker()
    {
        return app('queue.worker');
    }

    /**
     * Get the options for the worker.
     *
     * @return \Illuminate\Queue\WorkerOptions
     */
    protected function workerOptions()
    {
        return tap(new WorkerOptions, function ($options) {
            $options->sleep = 0;
            $options->maxTries = 1;
        });
    }

    /**
     * Publish package-built dashboard assets into the Testbench public path.
     */
    protected function publishHorizonDashboardAssets(): void
    {
        $packageBuild = app(PackageBuild::class)->path();

        if (! is_dir($packageBuild) || ! is_file($packageBuild.DIRECTORY_SEPARATOR.'manifest.json')) {
            return;
        }

        app(AssetsPublisher::class)->publish(
            destination: app(AssetPath::class)->absolute(),
            force: false,
            source: $packageBuild,
        );
    }

    /**
     * Get the service providers for the package.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            'Inertia\ServiceProvider',
            'Laravel\Horizon\HorizonServiceProvider',
        ];
    }

    /**
     * Configure the environment.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('queue.default', 'redis');
        // Full-suite PHPUnit memory often exceeds the production default (64MB),
        // which would make MonitorMasterSupervisorMemory terminate masters mid-loop.
        $app['config']->set('horizon.memory_limit', 512);

        RedisClusterHelper::configure($app);

        Redis::clearResolvedInstances();
    }
}
