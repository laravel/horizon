<?php

namespace Laravel\Horizon\Tests\Feature\Listeners;

use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Events\WorkerProcessRestarting;
use Laravel\Horizon\Listeners\LogWorkerProcessRestart;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\WorkerProcess;
use Mockery;
use Symfony\Component\Process\Process;

class LogWorkerProcessRestartTest extends IntegrationTest
{
    public function test_it_logs_warning_for_memory_exceeded_exit_code()
    {
        Log::spy();

        $process = Mockery::mock(Process::class);
        $process->shouldReceive('getExitCode')->andReturn(12);
        $process->shouldReceive('getExitCodeText')->andReturn('Memory limit exceeded');

        $workerProcess = Mockery::mock(WorkerProcess::class);
        $workerProcess->process = $process;

        $listener = new LogWorkerProcessRestart;
        $listener->handle(new WorkerProcessRestarting($workerProcess));

        Log::shouldHaveReceived('warning')->withArgs(function ($message) {
            return str_contains($message, 'memory limit');
        })->once();
    }

    public function test_it_logs_info_for_normal_exit()
    {
        Log::spy();

        $process = Mockery::mock(Process::class);
        $process->shouldReceive('getExitCode')->andReturn(0);
        $process->shouldReceive('getExitCodeText')->andReturn('OK');

        $workerProcess = Mockery::mock(WorkerProcess::class);
        $workerProcess->process = $process;

        $listener = new LogWorkerProcessRestart;
        $listener->handle(new WorkerProcessRestarting($workerProcess));

        Log::shouldHaveReceived('info')->withArgs(function ($message) {
            return str_contains($message, 'restarted');
        })->once();
    }

    public function test_it_logs_info_for_unexpected_exit()
    {
        Log::spy();

        $process = Mockery::mock(Process::class);
        $process->shouldReceive('getExitCode')->andReturn(1);
        $process->shouldReceive('getExitCodeText')->andReturn('General error');

        $workerProcess = Mockery::mock(WorkerProcess::class);
        $workerProcess->process = $process;

        $listener = new LogWorkerProcessRestart;
        $listener->handle(new WorkerProcessRestarting($workerProcess));

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            return str_contains($message, 'restarted') && $context['reason'] === 'unexpected exit';
        })->once();
    }
}
