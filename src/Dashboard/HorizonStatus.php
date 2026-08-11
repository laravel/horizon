<?php

declare(strict_types=1);

namespace Laravel\Horizon\Dashboard;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;

final readonly class HorizonStatus
{
    public function __construct(private MasterSupervisorRepository $masters)
    {
    }

    /**
     * Shell / Inertia status, including mixed master states.
     *
     * Returns inactive, paused, partially_paused, or running.
     */
    public function current(): string
    {
        $masters = collect($this->masters->all());

        if ($masters->isEmpty()) {
            return 'inactive';
        }

        $pausedCount = $masters
            ->filter(fn (object $master): bool => $master->status === 'paused')
            ->count();

        if ($pausedCount === $masters->count()) {
            return 'paused';
        }

        if ($pausedCount > 0) {
            return 'partially_paused';
        }

        return 'running';
    }

    /**
     * Legacy public-API status enum (running | paused | inactive).
     *
     * Mixed master fleets collapse to running so /api/stats stays compatible.
     */
    public function legacy(): string
    {
        $status = $this->current();

        return $status === 'partially_paused' ? 'running' : $status;
    }

    public function pausedMasters(): int
    {
        return collect($this->masters->all())
            ->filter(fn (object $master): bool => $master->status === 'paused')
            ->count();
    }
}
