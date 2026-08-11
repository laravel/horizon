<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Laravel\Horizon\Assets\AssetPath;
use Laravel\Horizon\Assets\AssetsPublisher;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

/**
 * @deprecated Use horizon:assets or horizon:install to publish dashboard assets.
 */
#[AsCommand(name: 'horizon:publish')]
class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:publish
        {--force : Refresh previously published assets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deprecated: use horizon:assets or horizon:install to publish Horizon assets';

    /**
     * Publish packaged dashboard assets (kept for existing Composer hooks).
     */
    public function handle(AssetsPublisher $publisher, AssetPath $assetPath): int
    {
        $this->components->warn('horizon:publish is deprecated; use horizon:assets or horizon:install.');

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
