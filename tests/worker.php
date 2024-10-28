<?php

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;

use function Orchestra\Testbench\container;

// Configure the application...
$app = container()->createApplication();
$app->register(Laravel\Horizon\HorizonServiceProvider::class);
$app->make('config')->set('queue.default', 'redis');

$worker = new Worker(
    $app->make(QueueManager::class),
    $app->make(Dispatcher::class),
    $app->make(ExceptionHandler::class),
    function () use ($app) {
        return $app->isDownForMaintenance();
    }
);

// Pause the worker if needed...
if (in_array('--paused', $_SERVER['argv'])) {
    $worker->paused = true;
}

// Start the daemon loop.
$worker->daemon(
    'redis', 'default', new WorkerOptions
);
