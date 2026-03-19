<?php

namespace Laravel\Horizon\Listeners;

use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Events\UnableToLaunchProcess;

class LogUnableToLaunchProcess
{
    /**
     * Handle the event.
     *
     * @param  \Laravel\Horizon\Events\UnableToLaunchProcess  $event
     * @return void
     */
    public function handle(UnableToLaunchProcess $event)
    {
        $exitCode = $event->process->process->getExitCode();

        Log::error('Horizon worker process failed to start and will pause for 60 seconds before retrying. This may cause queue processing delays.', [
            'exit_code' => $exitCode,
            'exit_code_text' => $event->process->process->getExitCodeText(),
            'reason' => $exitCode === 12
                ? 'memory limit exceeded — increase the "memory" option in your Horizon supervisor configuration'
                : 'unknown',
        ]);
    }
}
