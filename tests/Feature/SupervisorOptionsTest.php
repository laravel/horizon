<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\SupervisorOptions;
use Laravel\Horizon\Tests\IntegrationTest;

class SupervisorOptionsTest extends IntegrationTest
{
    public function test_default_queue_is_used_when_null_is_given()
    {
        $options = new SupervisorOptions('name', 'redis');
        $this->assertSame('default', $options->queue);
    }

    public function test_directory_is_used_when_given()
    {
        $options = new SupervisorOptions(
            'name',
            'redis',
            null,
            'default',
            'off',
            0,
            0,
            0,
            1,
            1,
            128,
            60,
            3,
            0,
            false,
            0,
            3,
            1,
            0,
            0,
            '/tmp'
        );
        $this->assertSame('/tmp', $options->directory);

        $options = SupervisorOptions::fromArray([
            'name' => 'name',
            'connection' => 'redis',
            'directory' => '/tmp',
        ]);
        $this->assertSame('/tmp', $options->directory);

        $this->assertArrayHasKey('directory', $options->toArray());
        $this->assertSame('/tmp', $options->toArray()['directory']);

        $this->assertStringContainsString('"directory":"\/tmp"', $options->toJson());
    }
}
