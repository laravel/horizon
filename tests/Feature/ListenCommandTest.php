<?php

namespace Laravel\Horizon\Tests\Feature;

use InvalidArgumentException;
use Laravel\Horizon\Tests\IntegrationTest;

class ListenCommandTest extends IntegrationTest
{
    public function test_listen_command_requires_watch_configuration()
    {
        config(['horizon.watch' => []]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('List of directories/files to watch not found.');

        $this->artisan('horizon:listen');
    }

    public function test_listen_command_requires_watch_configuration_to_be_set()
    {
        config(['horizon.watch' => null]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('List of directories/files to watch not found.');

        $this->artisan('horizon:listen');
    }

    public function test_listen_command_requires_watch_configuration_key_to_exist()
    {
        $config = config('horizon');
        unset($config['watch']);
        config(['horizon' => $config]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('List of directories/files to watch not found.');

        $this->artisan('horizon:listen');
    }

    public function test_listen_command_fails_gracefully_when_node_is_not_available()
    {
        config(['horizon.watch' => ['app', 'config']]);

        // Mock the ExecutableFinder to return null (node not found)
        $this->mock(\Symfony\Component\Process\ExecutableFinder::class, function ($mock) {
            $mock->shouldReceive('find')->with('node')->andReturn(null);
        });

        $this->artisan('horizon:listen')
            ->expectsOutputToContain('Unable to start file watcher')
            ->assertExitCode(1);
    }
}
