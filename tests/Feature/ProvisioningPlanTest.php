<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\MasterSupervisorCommands\AddSupervisor;
use Laravel\Horizon\ProvisioningPlan;
use Laravel\Horizon\Tests\IntegrationTest;

class ProvisioningPlanTest extends IntegrationTest
{
    public function test_supervisors_are_added()
    {
        $plan = [
            'production' => [
                'supervisor-1' => [
                    'connection' => 'redis',
                    'queue' => 'first',
                    'max_processes' => 20,
                ],
            ],
        ];

        $plan = new ProvisioningPlan(MasterSupervisor::name(), $plan);
        $plan->deploy('production');

        $commands = Redis::connection('horizon')->lrange(
            'commands:'.MasterSupervisor::commandQueueFor(MasterSupervisor::name()), 0, -1
        );

        $this->assertCount(1, $commands);
        $command = (object) json_decode($commands[0], true);
        $this->assertSame(AddSupervisor::class, $command->command);
        $this->assertSame('first', $command->options['queue']);
        $this->assertSame(20, $command->options['maxProcesses']);
    }

    public function test_supervisors_are_added_by_wildcard()
    {
        $plan = [
            'production' => [
                'supervisor-1' => [
                    'connection' => 'redis',
                    'queue' => 'first',
                    'max_processes' => 20,
                ],
            ],
        ];

        $plan = new ProvisioningPlan(MasterSupervisor::name(), $plan);
        $plan->deploy('production');

        $commands = Redis::connection('horizon')->lrange(
            'commands:'.MasterSupervisor::commandQueueFor(MasterSupervisor::name()), 0, -1
        );

        $this->assertCount(1, $commands);
        $command = (object) json_decode($commands[0], true);
        $this->assertSame(AddSupervisor::class, $command->command);
        $this->assertSame('first', $command->options['queue']);
        $this->assertSame(20, $command->options['maxProcesses']);
    }

    public function test_plan_is_converted_into_array_of_supervisor_options()
    {
        $plan = [
            'production' => [
                'supervisor-1' => [
                    'connection' => 'redis',
                    'queue' => 'default',
                    'balance' => true,
                    'auto_scale' => true,
                ],

                'supervisor-2' => [
                    'connection' => 'redis',
                    'queue' => 'default',
                ],
            ],

            'local' => [
                'supervisor-2' => [
                    'connection' => 'redis',
                    'queue' => 'local-supervisor-2-queue',
                    'max_processes' => 20,
                ],
            ],
        ];

        $results = (new ProvisioningPlan(MasterSupervisor::name(), $plan))->toSupervisorOptions();

        $this->assertSame(MasterSupervisor::name().':supervisor-1', $results['production']['supervisor-1']->name);
        $this->assertSame('redis', $results['production']['supervisor-1']->connection);
        $this->assertSame('default', $results['production']['supervisor-1']->queue);
        $this->assertTrue($results['production']['supervisor-1']->balance);
        $this->assertTrue($results['production']['supervisor-1']->autoScale);

        $this->assertSame(20, $results['local']['supervisor-2']->maxProcesses);
    }

    public function test_backoff_is_translated_to_string_form()
    {
        $plan = [
            'local' => [
                'supervisor-2' => [
                    'connection' => 'redis',
                    'queue' => 'local-supervisor-2-queue',
                    'backoff' => [30, 60],
                ],
            ],
        ];

        $results = (new ProvisioningPlan(MasterSupervisor::name(), $plan))->toSupervisorOptions();

        $this->assertSame('30,60', $results['local']['supervisor-2']->backoff);
    }
}
