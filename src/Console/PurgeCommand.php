<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\ProcessRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\ProcessInspector;

class PurgeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:purge
                            {--signal=SIGTERM : The signal to send to the rogue processes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Terminate any rogue Horizon processes';

    /**
     * @var \Laravel\Horizon\Contracts\SupervisorRepository
     */
    private $supervisors;

    /**
     * @var \Laravel\Horizon\Contracts\ProcessRepository
     */
    private $processes;

    /**
     * @var \Laravel\Horizon\ProcessInspector
     */
    private $inspector;

    /**
     * Create a new command instance.
     *
     * @param  \Laravel\Horizon\Contracts\SupervisorRepository  $supervisors
     * @param  \Laravel\Horizon\Contracts\ProcessRepository  $processes
     * @param  \Laravel\Horizon\ProcessInspector  $inspector
     * @return void
     */
    public function __construct(
        SupervisorRepository $supervisors,
        ProcessRepository $processes,
        ProcessInspector $inspector
    ) {
        parent::__construct();

        $this->supervisors = $supervisors;
        $this->processes = $processes;
        $this->inspector = $inspector;
    }

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Horizon\Contracts\MasterSupervisorRepository  $masters
     * @return void
     */
    public function handle(MasterSupervisorRepository $masters)
    {
        $signal = is_numeric($signal = $this->option('signal'))
                        ? $signal
                        : constant($signal);

        foreach ($masters->names() as $master) {
            if (Str::startsWith($master, MasterSupervisor::basename())) {
                $this->purge($master, $signal);
            }
        }
    }

    /**
     * Purge any orphan processes.
     *
     * @param  string  $master
     * @param  int  $signal
     * @return void
     */
    public function purge($master, $signal = SIGTERM)
    {
        $this->recordOrphans($master, $signal);

        $expired = $this->processes->orphanedFor(
            $master, $this->supervisors->longestActiveTimeout()
        );

        collect($expired)
            ->whenNotEmpty(fn () => $this->components->info('Sending TERM signal to expired processes of ['.$master.']'))
            ->each(function ($processId) use ($master, $signal) {
                $this->components->task("Process: $processId", function () use ($processId, $signal) {
                    exec("kill -s {$signal} {$processId}");
                });

                $this->processes->forgetOrphans($master, [$processId]);
            })->whenNotEmpty(fn () => $this->output->writeln(''));
    }

    /**
     * Record the orphaned Horizon processes.
     *
     * @param  string  $master
     * @param  int  $signal
     * @return void
     */
    protected function recordOrphans($master, $signal)
    {
        $this->processes->orphaned(
            $master, $orphans = $this->inspector->orphaned()
        );

        collect($orphans)
            ->whenNotEmpty(fn () => $this->components->info('Sending TERM signal to orphaned processes of ['.$master.']'))
            ->each(function ($processId) use ($signal) {
                $result = true;

                $this->components->task("Process: $processId", function () use ($processId, $signal, &$result) {
                    return $result = posix_kill($processId, $signal);
                });

                if (! $result) {
                    $this->components->error("Failed to kill orphan process: {$processId} (".posix_strerror(posix_get_last_error()).')');
                }
            })->whenNotEmpty(fn () => $this->output->writeln(''));
    }
}
