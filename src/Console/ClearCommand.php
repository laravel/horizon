<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;

class ClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all horizon queues';

    /**
     * @var \Illuminate\Queue\QueueManager
     */
    private $manager;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    public function __construct(
        \Illuminate\Queue\QueueManager $manager
    ) {
        parent::__construct();

        $this->manager = $manager;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $environment = \App::environment();

        $supervisors = config("horizon.environments.{$environment}", []);

        collect($supervisors)->map(function($supervisor) {
            return [
                'connection' => $supervisor['connection'],
                'queue' => $supervisor['queue'],
            ];
        })->groupBy('connection')->map(function($connection) {
            return $connection->pluck('queue')->flatten()->unique()->values();
        })->each(function($queues, $connectionName) {
            $connection = $this->manager->connection($connectionName);

            $queues->each(function($queue) use ($connection, $connectionName) {
                $counter = 0;

                while ($job = $connection->pop($queue)) {
                    $job->delete();
                    $counter++;

                }

                $this->output->writeln("<comment>Cleared {$counter} jobs in the {$queue} queue using {$connectionName} connection.</comment>");
            });
        });
    }
}
