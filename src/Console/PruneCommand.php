<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Laravel\Horizon\Models\HorizonCommand as HorizonCommandModel;
use Laravel\Horizon\Models\HorizonJob;
use Laravel\Horizon\Models\HorizonJobReference;
use Laravel\Horizon\Models\HorizonLock;
use Laravel\Horizon\Models\HorizonMasterSupervisor;
use Laravel\Horizon\Models\HorizonMetricSnapshot;
use Laravel\Horizon\Models\HorizonSupervisor;
use Laravel\Horizon\Models\HorizonTag;

class PruneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:prune
                            {--pretend : Display the number of prunable records found instead of deleting them}
                            {--chunk=1000 : The number of models to retrieve per chunk of models to be deleted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune expired Horizon records across the database driver tables.';

    /**
     * Horizon models which support the Prunable trait.
     *
     * @var array<int, class-string>
     */
    protected array $models = [
        HorizonJob::class,
        HorizonJobReference::class,
        HorizonTag::class,
        HorizonMetricSnapshot::class,
        HorizonSupervisor::class,
        HorizonMasterSupervisor::class,
        HorizonLock::class,
        HorizonCommandModel::class,
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        foreach ($this->models as $model) {
            $this->option('pretend')
                ? $this->pretend($model)
                : $this->prune($model);
        }

        return self::SUCCESS;
    }

    /**
     * Prune the given model.
     *
     * @param  class-string  $model
     * @return void
     */
    protected function prune(string $model): void
    {
        $instance = new $model;

        $chunk = property_exists($instance, 'prunableChunkSize') && $instance->prunableChunkSize
            ? (int) $instance->prunableChunkSize
            : (int) $this->option('chunk');

        $total = $instance->pruneAll($chunk);

        $this->components->twoColumnDetail($model, "{$total} records");
    }

    /**
     * Report how many rows would be pruned, without deleting.
     *
     * @param  class-string  $model
     * @return void
     */
    protected function pretend(string $model): void
    {
        $count = (new $model)->prunable()->count();

        $this->components->twoColumnDetail($model, "{$count} records would be pruned");
    }
}
