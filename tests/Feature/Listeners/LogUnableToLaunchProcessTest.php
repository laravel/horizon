<?php

namespace Laravel\Horizon\Tests\Feature\Listeners;

use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Events\UnableToLaunchProcess;
use Laravel\Horizon\Listeners\LogUnableToLaunchProcess;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\WorkerProcess;
use Mockery;
use Symfony\Component\Process\Process;

class LogUnableToLaunchProcessTest extends IntegrationTest
{
    public function test_it_logs_error_with_exit_code_and_reason()
    {
        Log::spy();

        $process = Mockery::mock(Process::class);
        $process->shouldReceive('getExitCode')->andReturn(12);
        $process->shouldReceive('getExitCodeText')->andReturn('Memory limit exceeded');

        $workerProcess = Mockery::mock(WorkerProcess::class);
        $workerProcess->process = $process;

        $listener = new LogUnableToLaunchProcess;
        $listener->handle(new UnableToLaunchProcess($workerProcess));

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return str_contains($message, 'failed to start')
                && $context['exit_code'] === 12
                && str_contains($context['reason'], 'memory');
        })->once();
    }

    public function test_it_logs_unknown_reason_for_non_memory_exit_code()
    {
        Log::spy();

        $process = Mockery::mock(Process::class);
        $process->shouldReceive('getExitCode')->andReturn(1);
        $process->shouldReceive('getExitCodeText')->andReturn('General error');

        $workerProcess = Mockery::mock(WorkerProcess::class);
        $workerProcess->process = $process;

        $listener = new LogUnableToLaunchProcess;
        $listener->handle(new UnableToLaunchProcess($workerProcess));

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return $context['reason'] === 'unknown';
        })->once();
    }
}
