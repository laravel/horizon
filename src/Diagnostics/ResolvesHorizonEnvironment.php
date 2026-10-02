<?php

namespace Laravel\Horizon\Diagnostics;

use Laravel\Doctor\Support\Configured;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Throwable;

trait ResolvesHorizonEnvironment
{
    /**
     * Get the environment Horizon is currently using or would use when started.
     */
    private function horizonEnvironment(): string
    {
        try {
            $environment = collect(app(MasterSupervisorRepository::class)->all())
                ->pluck('environment')
                ->first(fn ($environment): bool => is_string($environment) && $environment !== '');

            if (is_string($environment)) {
                return $environment;
            }
        } catch (Throwable) {
            // Fall back to the environment a new Horizon process would use.
        }

        return Configured::string('horizon.env')
            ?? Configured::string('app.env', 'production');
    }
}
