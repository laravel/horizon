<?php

namespace Laravel\Horizon\Diagnostics;

use Illuminate\Support\Str;
use Laravel\Doctor\Diagnostic;
use Laravel\Doctor\EnvironmentMode;
use Laravel\Doctor\Results\DiagnosticResult;
use Laravel\Doctor\Results\Link;
use Laravel\Doctor\Results\Message;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\ProvisioningPlan;
use Laravel\Horizon\SupervisorOptions;
use Throwable;

class HorizonEnvironmentIsDefined extends Diagnostic
{
    use ResolvesHorizonEnvironment;

    public string $name = 'Horizon environment is defined';

    public string $group = 'horizon';

    /**
     * Get the diagnostic's named message definitions.
     *
     * @return array<string, string|Message>
     */
    protected function messages(): array
    {
        return [
            'no-environments' => Message::make(
                summary: 'No environments are defined in Horizon\'s configuration.',
                remediation: 'Add your environments to `horizon.environments` so Horizon can start supervisors.',
            )->link(Link::docs('horizon', 'environments')),
            'invalid-configuration' => Message::make(
                summary: 'The Horizon environment configuration is invalid.',
                remediation: 'Correct the supervisor options in `config/horizon.php`.',
            )->link(Link::docs('horizon', 'configuration')),
            'not-defined' => Message::make(
                summary: 'Horizon does not define supervisors for the [{environment}] environment.',
                remediation: 'Add a [{environment}] entry (or a wildcard) to `horizon.environments` so Horizon starts supervisors in this environment.',
            )->link(Link::docs('horizon', 'environments')),
            'no-processes' => Message::make(
                summary: 'No Horizon supervisors for the [{environment}] environment can start worker processes.',
                remediation: 'Set `maxProcesses` to at least 1 for a supervisor in the [{environment}] environment.',
            )->link(Link::docs('horizon', 'environments')),
            'defined' => 'Horizon defines supervisors for the [{environment}] environment.',
        ];
    }

    /**
     * Run the diagnostic.
     */
    public function check(): DiagnosticResult
    {
        $environments = config('horizon.environments');

        if (! is_array($environments) || $environments === []) {
            return EnvironmentMode::current()->isProduction()
                ? $this->fail('no-environments')
                : $this->warn('no-environments');
        }

        try {
            $plan = new ProvisioningPlan(
                MasterSupervisor::name(),
                $environments,
                (array) config('horizon.defaults', []),
            );
        } catch (Throwable $e) {
            return $this->fail('invalid-configuration')->withDetails($e->getMessage());
        }

        $environment = $this->horizonEnvironment();

        $supervisors = collect($plan->parsed)->first(
            fn ($supervisors, string $name): bool => Str::is($name, $environment),
        );

        if ($supervisors === null) {
            return EnvironmentMode::current()->isProduction()
                ? $this->fail('not-defined', ['environment' => $environment])
                : $this->warn('not-defined', ['environment' => $environment]);
        }

        if (collect($supervisors)->every(fn (SupervisorOptions $options): bool => $options->maxProcesses < 1)) {
            return EnvironmentMode::current()->isProduction()
                ? $this->fail('no-processes', ['environment' => $environment])
                : $this->warn('no-processes', ['environment' => $environment]);
        }

        return $this->pass('defined', ['environment' => $environment]);
    }
}
