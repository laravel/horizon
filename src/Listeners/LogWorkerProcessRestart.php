<?php

namespace Laravel\Horizon\Listeners;

use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Events\WorkerProcessRestarting;

class LogWorkerProcessRestart
{
    /**
     * Handle the event.
     *
     * @param  \Laravel\Horizon\Events\WorkerProcessRestarting  $event
     * @return void
     */
    public function handle(WorkerProcessRestarting $event)
    {
        $exitCode = $event->process->process->getExitCode();

        if ($exitCode === 12) {
            Log::warning('Horizon worker restarting because it exceeded its memory limit. If this happens frequently, consider increasing the "memory" option in your Horizon configuration.', [
                'exit_code' => $exitCode,
                'exit_code_text' => $event->process->process->getExitCodeText(),
            ]);
        } else {
            Log::info('Horizon worker process is being restarted.', [
                'exit_code' => $exitCode,
                'exit_code_text' => $event->process->process->getExitCodeText(),
                'reason' => $exitCode === 0 ? 'normal exit' : 'unexpected exit',
            ]);
        }
    }
}
