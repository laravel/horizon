<?php

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Orchestra\Testbench\Traits\CreatesApplication;

$appLoader = new class {
    use CreatesApplication;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application   $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Define your environment setup.
    }
};

// Configure the application...
$app = $appLoader->createApplication();
$app->register(Laravel\Horizon\HorizonServiceProvider::class);
$app['config']->set('queue.default', 'redis');

// Create the worker...
$worker = app(Worker::class);

// Pause the worker if needed...
if (in_array('--paused', $_SERVER['argv'])) {
    $worker->paused = true;
}

// Start the daemon loop.
$worker->daemon(
    'redis', 'default', new WorkerOptions
);
