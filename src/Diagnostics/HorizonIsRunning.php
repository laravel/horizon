<?php

namespace Laravel\Horizon\Diagnostics;

use Laravel\Doctor\Diagnostic;
use Laravel\Doctor\EnvironmentMode;
use Laravel\Doctor\Results\DiagnosticResult;
use Laravel\Doctor\Results\Link;
use Laravel\Doctor\Results\Message;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Throwable;

class HorizonIsRunning extends Diagnostic
{
    public string $name = 'Horizon is running';

    public string $group = 'horizon';

    /**
     * Get the diagnostic's named message definitions.
     *
     * @return array<string, string|Message>
     */
    protected function messages(): array
    {
        return [
            'redis-unreachable' => Message::make(
                summary: 'The Horizon Redis connection could not be reached.',
                remediation: 'Check `horizon.use` and the corresponding Redis connection configuration.',
            )->link(Link::docs('horizon', 'configuration')),
            'not-running' => Message::make(
                summary: 'Horizon is not running.',
                remediation: 'Run `php artisan horizon` under a process monitor such as Supervisor to process queued jobs.',
            )->link(Link::docs('horizon', 'deploying-horizon')),
            'paused' => Message::make(
                summary: 'At least one Horizon master supervisor is paused.',
                remediation: 'Run `php artisan horizon:continue` to resume processing queued jobs.',
            ),
            'running' => 'Horizon is running.',
        ];
    }

    /**
     * Run the diagnostic.
     */
    public function check(): DiagnosticResult
    {
        try {
            $masters = app(MasterSupervisorRepository::class)->all();
        } catch (Throwable $e) {
            $result = EnvironmentMode::current()->isProduction()
                ? $this->fail('redis-unreachable')
                : $this->warn('redis-unreachable');

            return $result->withDetails($e->getMessage());
        }

        if ($masters === []) {
            return EnvironmentMode::current()->isProduction()
                ? $this->fail('not-running')
                : $this->notice('not-running');
        }

        if ($this->anyPaused($masters)) {
            return $this->warn('paused');
        }

        return $this->pass('running');
    }

    /**
     * Determine whether any master supervisor is paused.
     *
     * @param  array<int|string, object>  $masters
     */
    private function anyPaused(array $masters): bool
    {
        return collect($masters)->contains(
            fn (object $master): bool => ($master->status ?? null) === 'paused',
        );
    }
}
