<?php

declare(strict_types=1);

namespace Laravel\Horizon\Dashboard;

use Illuminate\Support\Collection;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\ProvisioningPlan;

final readonly class MasterSupervisors
{
    public function __construct(
        private MasterSupervisorRepository $masters,
        private SupervisorRepository $supervisors,
    ) {
    }

    /**
     * @return Collection<string, object>
     */
    public function get(): Collection
    {
        $masters = collect($this->masters->all())->keyBy('name')->sortBy('name');
        $supervisors = collect($this->supervisors->all())->sortBy('name')->groupBy('master');

        return $masters->each(function (object $master, string $name) use ($supervisors): void {
            $master->supervisors = ($supervisors->get($name) ?? collect())
                ->merge(
                    collect(ProvisioningPlan::get($name)->plan[
                        $master->environment ?? config('horizon.env') ?? config('app.env')
                    ] ?? [])->map(fn (array $value, string $key): object => (object) [
                        'name' => $name.':'.$key,
                        'master' => $name,
                        'status' => 'inactive',
                        'processes' => [],
                        'options' => [
                            'queue' => array_key_exists('queue', $value) && is_array($value['queue'])
                                ? implode(',', $value['queue'])
                                : ($value['queue'] ?? ''),
                            'balance' => $value['balance'] ?? null,
                        ],
                    ])
                )
                ->unique('name')
                ->values();
        });
    }
}
