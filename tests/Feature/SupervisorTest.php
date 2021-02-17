<?php

namespace Laravel\Horizon\Tests\Feature;

use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\AutoScaler;
use Laravel\Horizon\Contracts\HorizonCommandQueue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Events\WorkerProcessRestarting;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\PhpBinary;
use Laravel\Horizon\Supervisor;
use Laravel\Horizon\SupervisorCommands\Scale;
use Laravel\Horizon\SupervisorOptions;
use Laravel\Horizon\SystemProcessCounter;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\WorkerCommandString;
use Mockery;

class SupervisorTest extends IntegrationTest
{
    public $phpBinary;
    public $supervisor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->phpBinary = PhpBinary::path();
    }

    protected function tearDown(): void
    {
        // Terminate all remaining processes and wait for them to exit...
        if ($this->supervisor) {
            $this->supervisor->processes()->each->terminate();

            while (count($this->supervisor->processes()->filter->isRunning()) > 0) {
                usleep(250 * 1000);
            }
        }

        parent::tearDown();
    }

    public function test_supervisor_can_start_worker_process_with_given_options()
    {
        Queue::push(new Jobs\BasicJob);
        $this->assertSame(1, $this->recentJobs());

        $this->supervisor = $supervisor = new Supervisor($this->supervisorOptions());

        $supervisor->scale(1);
        $supervisor->loop();

        $this->wait(function () {
            $this->assertSame('completed', resolve(JobRepository::class)->getRecent()[0]->status);
        });

        $this->assertCount(1, $supervisor->processes());

        $host = MasterSupervisor::name();
        $this->assertSame(
            'exec '.$this->phpBinary.' worker.php redis --name=default --supervisor='.$host.':name --backoff=0 --max-time=0 --max-jobs=0 --memory=128 --queue="default" --sleep=3 --timeout=60 --tries=0',
            $supervisor->processes()[0]->getCommandLine()
        );
    }

    public function test_supervisor_starts_multiple_pools_when_balancing()
    {
        $options = $this->supervisorOptions();
        $options->balance = 'simple';
        $options->queue = 'first,second';
        $this->supervisor = $supervisor = new Supervisor($options);

        $supervisor->scale(2);
        $this->assertCount(2, $supervisor->processes());

        $host = MasterSupervisor::name();

        $this->assertSame(
            'exec '.$this->phpBinary.' worker.php redis --name=default --supervisor='.$host.':name --backoff=0 --max-time=0 --max-jobs=0 --memory=128 --queue="first" --sleep=3 --timeout=60 --tries=0',
            $supervisor->processes()[0]->getCommandLine()
        );

        $this->assertSame(
            'exec '.$this->phpBinary.' worker.php redis --name=default --supervisor='.$host.':name --backoff=0 --max-time=0 --max-jobs=0 --memory=128 --queue="second" --sleep=3 --timeout=60 --tries=0',
            $supervisor->processes()[1]->getCommandLine()
        );
    }

    public function test_recent_jobs_are_correctly_maintained()
    {
        $id = Queue::push(new Jobs\BasicJob);
        $this->assertSame(1, $this->recentJobs());

        $this->supervisor = $supervisor = new Supervisor($options = $this->supervisorOptions());

        $supervisor->scale(1);
        $supervisor->loop();

        $this->wait(function () {
            $this->assertSame(1, $this->recentJobs());
        });

        $this->wait(function () use ($id) {
            $this->assertGreaterThan(0, Redis::connection('horizon')->ttl($id));
        });
    }

    public function test_supervisor_monitors_worker_processes()
    {
        $this->supervisor = $supervisor = new Supervisor($options = $this->supervisorOptions());
        // Force underlying worker to fail...
        WorkerCommandString::$command = 'php wrong.php';

        // Start the supervisor...
        $supervisor->scale(1);
        $supervisor->loop();
        usleep(250 * 1000);

        $supervisor->processes()[0]->restartAgainAt = CarbonImmutable::now()->subMinutes(10);

        // Make sure that the worker attempts restart...
        $restarted = false;
        Event::listen(WorkerProcessRestarting::class, function () use (&$restarted) {
            $restarted = true;
        });

        $supervisor->loop();
        $supervisor->loop();
        $supervisor->loop();

        $this->assertTrue($restarted);
    }

    public function test_exceptions_are_caught_and_handled_during_loop()
    {
        $exceptions = Mockery::mock(ExceptionHandler::class);
        $exceptions->shouldReceive('report')->once();
        $this->app->instance(ExceptionHandler::class, $exceptions);

        $this->supervisor = $supervisor = new Fakes\SupervisorThatThrowsException($options = $this->supervisorOptions());

        $supervisor->loop();
    }

    public function test_supervisor_information_is_persisted()
    {
        $this->supervisor = $supervisor = new Supervisor($options = $this->supervisorOptions());
        $options->queue = 'default,another';

        $supervisor->scale(2);
        usleep(100 * 1000);

        $supervisor->loop();

        $record = resolve(SupervisorRepository::class)->find($supervisor->name);
        $this->assertSame('running', $record->status);
        $this->assertSame(2, collect($record->processes)->sum());
        $this->assertSame(2, $record->processes['redis:default,another']);
        $this->assertTrue(isset($record->pid));
        $this->assertSame('redis', $record->options['connection']);

        $supervisor->pause();
        $supervisor->loop();

        $record = resolve(SupervisorRepository::class)->find($supervisor->name);
        $this->assertSame('paused', $record->status);
    }

    public function test_supervisor_repository_returns_null_if_no_supervisor_exists_with_given_name()
    {
        $repository = resolve(SupervisorRepository::class);

        $this->assertNull($repository->find('nothing'));
    }

    public function test_processes_can_be_scaled_up()
    {
        $this->supervisor = $supervisor = new Supervisor($options = $this->supervisorOptions());

        $supervisor->scale(2);
        $supervisor->loop();
        usleep(100 * 1000);

        $this->assertCount(2, $supervisor->processes());
        $this->assertTrue($supervisor->processes()[0]->isRunning());
        $this->assertTrue($supervisor->processes()[1]->isRunning());
    }

    public function test_processes_can_be_scaled_down()
    {
        $this->supervisor = $supervisor = new Supervisor($options = $this->supervisorOptions());
        $options->sleep = 0;

        $supervisor->scale(3);
        $supervisor->loop();
        usleep(100 * 1000);

        $this->assertCount(3, $supervisor->processes());

        $supervisor->scale(1);
        $supervisor->loop();
        usleep(100 * 1000);

        $this->assertCount(1, $supervisor->processes());
        $this->assertTrue($supervisor->processes()[0]->isRunning());

        // Give processes time to terminate...
        retry(10, function () use ($supervisor) {
            $this->assertCount(0, $supervisor->terminatingProcesses());
        }, 1000);
    }

    public function test_supervisor_can_restart_processes()
    {
        $this->supervisor = $supervisor = new Supervisor($options = $this->supervisorOptions());

        $supervisor->scale(1);
        $supervisor->loop();
        usleep(100 * 1000);

        $pid = $supervisor->processes()[0]->getPid();

        $supervisor->restart();
        usleep(100 * 1000);

        $this->assertNotEquals($pid, $supervisor->processes()[0]->getPid());
    }

    public function test_processes_can_be_paused_and_continued()
    {
        $options = $this->supervisorOptions();
        $options->sleep = 0;
        $this->supervisor = $supervisor = new Supervisor($options);

        $supervisor->scale(1);
        $supervisor->loop();
        $this->assertTrue($supervisor->processPools[0]->working);
        usleep(1100 * 1000);

        $supervisor->pause();
        $this->assertFalse($supervisor->processPools[0]->working);
        usleep(1100 * 1000);

        Queue::push(new Jobs\BasicJob);
        usleep(1100 * 1000);

        $this->assertSame(1, $this->recentJobs());

        $supervisor->continue();
        $this->assertTrue($supervisor->processPools[0]->working);

        $this->wait(function () {
            $this->assertSame('completed', resolve(JobRepository::class)->getRecent()[0]->status);
        });
    }

    public function test_dead_processes_are_not_restarted_when_paused()
    {
        $this->supervisor = $supervisor = new Supervisor($options = $this->supervisorOptions());

        $supervisor->scale(1);
        $supervisor->loop();
        usleep(250 * 1000);

        $process = $supervisor->processes()->first();
        $process->stop();
        $supervisor->pause();

        $supervisor->loop();
        usleep(250 * 1000);

        $this->assertFalse($process->isRunning());
    }

    public function test_supervisor_processes_can_be_terminated()
    {
        $this->supervisor = $supervisor = new Supervisor($options = $this->supervisorOptions());
        $options->sleep = 0;

        $supervisor->scale(1);
        $supervisor->loop();
        usleep(100 * 1000);

        $process = $supervisor->processes()->first();
        $this->assertTrue($process->isRunning());

        $process->terminate();
        usleep(500 * 1000);

        retry(10, function () use ($process) {
            $this->assertFalse($process->isRunning());
        }, 1000);
    }

    public function test_supervisor_can_prune_terminating_processes_and_return_total_process_count()
    {
        $this->supervisor = $supervisor = new Supervisor($options = $this->supervisorOptions());
        $options->sleep = 0;

        $supervisor->scale(1);
        usleep(100 * 1000);

        $supervisor->scale(0);
        usleep(500 * 1000);

        $this->assertSame(0, $supervisor->pruneAndGetTotalProcesses());
    }

    public function test_terminating_processes_that_are_stuck_are_hard_stopped()
    {
        $this->supervisor = $supervisor = new Supervisor($options = $this->supervisorOptions());
        $options->timeout = 0;
        $options->sleep = 0;

        $supervisor->scale(1);
        $supervisor->loop();
        usleep(100 * 1000);

        $process = $supervisor->processes()->first();
        $supervisor->processPools[0]->markForTermination($process);
        $supervisor->terminatingProcesses();

        $this->assertFalse($process->isRunning());
    }

    public function test_supervisor_process_terminates_all_workers_and_exits_on_full_termination()
    {
        $this->supervisor = $supervisor = new Fakes\SupervisorWithFakeExit($options = $this->supervisorOptions());

        $repository = resolve(SupervisorRepository::class);
        $repository->forgetDelay = 1;

        $supervisor->scale(1);
        usleep(100 * 1000);

        $supervisor->persist();
        $supervisor->terminate();

        $this->assertTrue($supervisor->exited);

        // Assert that the supervisor is removed...
        $this->assertNull(resolve(SupervisorRepository::class)->find($supervisor->name));
    }

    public function test_supervisor_loop_processes_pending_supervisor_commands()
    {
        $this->app->singleton(Commands\FakeCommand::class);

        $this->supervisor = $supervisor = new Supervisor($options = $this->supervisorOptions());

        $supervisor->scale(1);
        usleep(100 * 1000);

        resolve(HorizonCommandQueue::class)->push(
            $supervisor->name, Commands\FakeCommand::class, ['foo' => 'bar']
        );

        // Loop twice to make sure command is only called once...
        $supervisor->loop();
        $supervisor->loop();

        $command = resolve(Commands\FakeCommand::class);

        $this->assertSame(1, $command->processCount);
        $this->assertEquals($supervisor, $command->supervisor);
        $this->assertEquals(['foo' => 'bar'], $command->options);
    }

    public function test_supervisor_should_start_paused_workers_when_paused_and_scaling()
    {
        $options = $this->supervisorOptions();
        $options->sleep = 0;
        $this->supervisor = $supervisor = new Supervisor($options);

        $supervisor->scale(1);
        usleep(100 * 1000);

        resolve(HorizonCommandQueue::class)->push(
            $supervisor->name, Scale::class, ['scale' => 2]
        );
        $supervisor->pause();
        usleep(250 * 1000);

        $supervisor->loop();

        $this->assertSame(2, $supervisor->totalProcessCount());

        Queue::push(new Jobs\BasicJob);
        usleep(500 * 1000);

        $this->assertSame(1, $this->recentJobs());
    }

    public function test_auto_scaler_is_called_on_loop_when_auto_scaling()
    {
        $options = $this->supervisorOptions();
        $options->autoScale = true;
        $this->supervisor = $supervisor = new Supervisor($options);

        // Mock the scaler...
        $autoScaler = Mockery::mock(AutoScaler::class);
        $autoScaler->shouldReceive('scale')->once()->with($supervisor);
        $this->app->instance(AutoScaler::class, $autoScaler);

        // Start the supervisor...
        $supervisor->scale(1);
        usleep(100 * 1000);

        $supervisor->loop();

        // Call twice to make sure cool down works...
        $supervisor->loop();
    }

    public function test_auto_scaler_is_not_called_on_loop_during_cooldown()
    {
        $options = $this->supervisorOptions();
        $options->autoScale = true;
        $this->supervisor = $supervisor = new Supervisor($options);

        // Start the supervisor...
        $supervisor->scale(1);

        $time = CarbonImmutable::create();

        $this->assertNull($supervisor->lastAutoScaled);

        $supervisor->lastAutoScaled = null;
        CarbonImmutable::setTestNow($time);
        $supervisor->loop();
        $this->assertTrue($supervisor->lastAutoScaled->eq($time));

        $supervisor->lastAutoScaled = $time;
        CarbonImmutable::setTestNow($time->addSeconds($supervisor->options->balanceCooldown - 0.01));
        $supervisor->loop();
        $this->assertTrue($supervisor->lastAutoScaled->eq($time));

        $supervisor->lastAutoScaled = $time;
        CarbonImmutable::setTestNow($time->addSeconds($supervisor->options->balanceCooldown));
        $supervisor->loop();
        $this->assertTrue($supervisor->lastAutoScaled->eq($time->addSeconds($supervisor->options->balanceCooldown)));

        $supervisor->lastAutoScaled = $time;
        CarbonImmutable::setTestNow($time->addSeconds($supervisor->options->balanceCooldown + 0.01));
        $supervisor->loop();
        $this->assertTrue($supervisor->lastAutoScaled->eq($time->addSeconds($supervisor->options->balanceCooldown)));
    }

    public function test_supervisor_with_duplicate_name_cant_be_started()
    {
        $this->expectException(Exception::class);

        $options = $this->supervisorOptions();
        $this->supervisor = $supervisor = new Supervisor($options);
        $supervisor->persist();
        $anotherSupervisor = new Supervisor($options);

        $anotherSupervisor->monitor();
    }

    public function test_supervisor_processes_can_be_counted_externally()
    {
        SystemProcessCounter::$command = 'worker.php';
        $this->supervisor = $supervisor = new Supervisor($options = $this->supervisorOptions());

        $supervisor->scale(3);
        $supervisor->loop();

        $this->wait(function () use ($supervisor) {
            $this->assertSame(3, $supervisor->totalSystemProcessCount());
        });
    }

    public function test_supervisor_does_not_start_workers_until_looped_and_active()
    {
        SystemProcessCounter::$command = 'worker.php';
        $this->supervisor = $supervisor = new Supervisor($options = $this->supervisorOptions());

        $supervisor->scale(3);

        $this->wait(function () use ($supervisor) {
            $this->assertSame(0, $supervisor->totalSystemProcessCount());
        });

        $supervisor->working = false;
        $supervisor->loop();

        $this->wait(function () use ($supervisor) {
            $this->assertSame(0, $supervisor->totalSystemProcessCount());
        });

        $supervisor->working = true;
        $supervisor->loop();

        $this->wait(function () use ($supervisor) {
            $this->assertSame(3, $supervisor->totalSystemProcessCount());
        });
    }

    public function supervisorOptions()
    {
        return tap(new SupervisorOptions(MasterSupervisor::name().':name', 'redis'), function ($options) {
            $options->directory = realpath(__DIR__.'/../');
            WorkerCommandString::$command = 'exec '.$this->phpBinary.' worker.php';
        });
    }
}
