<?php

namespace Laravel\Horizon\Diagnostics;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Doctor\Diagnostic;
use Laravel\Doctor\EnvironmentMode;
use Laravel\Doctor\Results\DiagnosticResult;
use Laravel\Doctor\Results\Link;
use Laravel\Doctor\Results\Message;

class HorizonSnapshotIsScheduled extends Diagnostic
{
    public string $name = 'Horizon snapshot is scheduled';

    public string $group = 'horizon';

    /**
     * Get the diagnostic's named message definitions.
     *
     * @return array<string, string|Message>
     */
    protected function messages(): array
    {
        return [
            'scheduled' => 'Horizon metric snapshots are scheduled.',
            'not-scheduled' => Message::make(
                summary: 'Horizon metric snapshots are not scheduled.',
                remediation: 'Schedule `horizon:snapshot` every five minutes so the metrics dashboard has data.',
            )->link(Link::docs('horizon', 'metrics')),
        ];
    }

    /**
     * Run the diagnostic.
     */
    public function check(): DiagnosticResult
    {
        if ($this->snapshotIsScheduled()) {
            return $this->pass('scheduled');
        }

        return EnvironmentMode::current()->isProduction()
            ? $this->warn('not-scheduled')
            : $this->notice('not-scheduled');
    }

    /**
     * Determine whether metric snapshots are on the application's schedule.
     */
    private function snapshotIsScheduled(): bool
    {
        foreach (app(Schedule::class)->events() as $event) {
            if (str_contains((string) $event->command, 'horizon:snapshot')) {
                return true;
            }
        }

        return false;
    }
}
