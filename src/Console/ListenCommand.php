<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'horizon:listen')]
class ListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:listen
        {--environment= : The environment name}
        {--poll : Use polling for file watching}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Horizon and automatically restart workers on file changes';

    /**
     * The Horizon process instance.
     *
     * @var \Symfony\Component\Process\Process|null
     */
    protected $horizonProcess;

    /**
     * The file watcher process instance.
     *
     * @var \Symfony\Component\Process\Process|null
     */
    protected $watcherProcess;

    /**
     * Indicates if a termination signal has been received.
     *
     * @var int|null
     */
    protected $trappedSignal = null;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->components->info('Starting Horizon and watching for file changes...');

        $this->watcherProcess = $this->startWatcher();

        if ($this->watcherProcess->isTerminated()) {
            return $this->watcherFailed();
        }

        if (! $this->startHorizon()) {
            return Command::FAILURE;
        }

        $this->listenForChanges();

        return Command::SUCCESS;
    }

    /**
     * Start the file watcher process.
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function startWatcher()
    {
        if (empty($paths = config('horizon.watch'))) {
            throw new InvalidArgumentException(
                'List of directories / files to watch not found. Please update your "config/horizon.php" configuration file.',
            );
        }

        $process = new Process([
            (new ExecutableFinder)->find('node'),
            'file-watcher.cjs',
            json_encode(collect($paths)->map(fn ($path) => base_path($path))->values()->all()),
            $this->option('poll') ? '1' : '',
        ], __DIR__.'/../../bin', ['NODE_PATH' => base_path('node_modules')], null, null);

        $process->start();

        sleep(1);

        return $process;
    }

    /**
     * Start the Horizon process.
     *
     * @return bool
     */
    protected function startHorizon()
    {
        $command = 'php artisan horizon';

        if ($environment = $this->option('environment')) {
            $command .= ' --environment='.$environment;
        }

        $this->horizonProcess = Process::fromShellCommandline($command)
            ->setTimeout(null);

        $this->trap([SIGINT, SIGTERM, SIGQUIT], function ($signal) {
            $this->trappedSignal = $signal;

            $this->horizonProcess->stop(signal: $signal);
            $this->horizonProcess->wait();

            if ($this->watcherProcess) {
                $this->watcherProcess->stop();
            }
        });

        $this->horizonProcess->start();

        usleep(100000);

        return ! $this->horizonProcess->isTerminated();
    }

    /**
     * Listen for file changes and restart Horizon when detected.
     *
     * @return void
     */
    protected function listenForChanges()
    {
        while (! $this->trappedSignal) {
            if ($this->watcherProcess->getIncrementalOutput()) {
                $this->restartHorizon();
            }

            $this->output->write($this->horizonProcess->getIncrementalOutput());

            if (! $this->horizonProcess->isRunning()) {
                break;
            }

            usleep(500000);
        }
    }

    /**
     * Restart the Horizon process.
     *
     * @return void
     */
    protected function restartHorizon()
    {
        $this->components->info('File changed. Restarting Horizon...');

        $this->horizonProcess->stop();
        $this->horizonProcess->wait();

        $this->startHorizon();
    }

    /**
     * Handle watcher process failure.
     *
     * @return int
     */
    protected function watcherFailed()
    {
        $this->components->error(
            'Unable to start file watcher. Please ensure Node.js and the chokidar npm package are installed.',
        );

        $this->output->writeln($this->watcherProcess->getErrorOutput());

        return Command::FAILURE;
    }
}
