<?php

namespace Laravel\Horizon\Diagnostics;

use Illuminate\Support\Str;
use Laravel\Doctor\Diagnostic;
use Laravel\Doctor\EnvironmentMode;
use Laravel\Doctor\Results\DiagnosticResult;
use Laravel\Doctor\Results\Link;
use Laravel\Doctor\Results\Message;
use Laravel\Doctor\Support\Configured;
use Laravel\Doctor\Support\Details;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\ProvisioningPlan;
use Laravel\Horizon\SupervisorOptions;
use Throwable;

class HorizonProcessesDefaultRedisQueue extends Diagnostic
{
    use ResolvesHorizonEnvironment;

    public string $name = 'Horizon processes the default Redis queue';

    public string $group = 'horizon';

    /**
     * Get the diagnostic's named message definitions.
     *
     * @return array<string, string|Message>
     */
    protected function messages(): array
    {
        return [
            'no-default' => 'The application does not have a default queue connection configured.',
            'not-redis' => 'The default queue connection [{connection}] is not Redis-backed, so Horizon is not expected to process it.',
            'cannot-inspect' => 'Horizon queue coverage cannot be determined for the [{environment}] environment.',
            'covered' => 'Horizon processes the default Redis queue [{queue}].',
            'not-covered' => Message::make(
                summary: 'Horizon does not process the default Redis queue [{queue}].',
                remediation: 'Add the default queue to a runnable Horizon supervisor in the [{environment}] environment, or change the application default queue.',
            )->link(Link::docs('horizon', 'environments')),
        ];
    }

    /**
     * Run the diagnostic.
     */
    public function check(): DiagnosticResult
    {
        $connection = Configured::string('queue.default');

        if ($connection === null) {
            return $this->skip('no-default');
        }

        if (Configured::string("queue.connections.{$connection}.driver") !== 'redis') {
            return $this->skip('not-redis', ['connection' => $connection]);
        }

        $queue = Configured::string("queue.connections.{$connection}.queue", 'default');
        $target = "{$connection}:{$queue}";
        $environment = $this->horizonEnvironment();

        try {
            $plan = new ProvisioningPlan(
                MasterSupervisor::name(),
                (array) config('horizon.environments', []),
                (array) config('horizon.defaults', []),
            );
        } catch (Throwable $e) {
            return $this->skip('cannot-inspect', ['environment' => $environment])
                ->withDetails($e->getMessage());
        }

        $supervisors = collect($plan->parsed)->first(
            fn ($supervisors, string $name): bool => Str::is($name, $environment),
        );

        if ($supervisors === null) {
            return $this->skip('cannot-inspect', ['environment' => $environment]);
        }

        /** @var array<string, SupervisorOptions> $supervisorOptions */
        $supervisorOptions = collect($supervisors)->all();
        $watched = $this->watchedQueues($supervisorOptions);

        if (in_array($target, $watched, true)) {
            return $this->pass('covered', ['queue' => $target]);
        }

        $result = EnvironmentMode::current()->isProduction()
            ? $this->fail('not-covered', ['queue' => $target, 'environment' => $environment])
            : $this->warn('not-covered', ['queue' => $target, 'environment' => $environment]);

        return $result->withDetails(Details::bullets([
            "Default: {$target}",
            'Watched: '.($watched === [] ? 'none' : implode(', ', $watched)),
        ]));
    }

    /**
     * Get the connection and queue pairs watched by runnable supervisors.
     *
     * @param  array<string, SupervisorOptions>  $supervisors
     * @return list<string>
     */
    private function watchedQueues(array $supervisors): array
    {
        return collect($supervisors)
            ->filter(fn (SupervisorOptions $options): bool => $options->maxProcesses > 0)
            ->flatMap(fn (SupervisorOptions $options): array => array_map(
                fn (string $queue): string => "{$options->connection}:{$queue}",
                explode(',', (string) $options->queue),
            ))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
