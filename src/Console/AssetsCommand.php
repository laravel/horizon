<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Laravel\Horizon\Assets\AssetPath;
use Laravel\Horizon\Assets\AssetsPublisher;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'horizon:assets')]
class AssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:assets
        {--force : Refresh previously published assets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the Horizon dashboard compiled assets';

    /**
     * Execute the console command.
     */
    public function handle(AssetsPublisher $publisher, AssetPath $assetPath): int
    {
        try {
            $publisher->publish(
                destination: $assetPath->absolute(),
                force: (bool) $this->option('force'),
            );
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Horizon assets are ready.');

        return self::SUCCESS;
    }
}
