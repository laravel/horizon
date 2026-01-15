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
        $this->expectExceptionMessage('List of directories / files to watch not found.');

        $this->artisan('horizon:listen');
    }

    public function test_listen_command_requires_watch_configuration_to_be_set()
    {
        config(['horizon.watch' => null]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('List of directories / files to watch not found.');

        $this->artisan('horizon:listen');
    }

    public function test_listen_command_requires_watch_configuration_key_to_exist()
    {
        $config = config('horizon');
        unset($config['watch']);
        config(['horizon' => $config]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('List of directories / files to watch not found.');

        $this->artisan('horizon:listen');
    }
}
