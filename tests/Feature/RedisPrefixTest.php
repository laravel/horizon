<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Facades\Config;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Tests\IntegrationTest;

class RedisPrefixTest extends IntegrationTest
{
    public function test_prefix_can_be_configured()
    {
        config(['horizon.prefix' => 'custom:']);

        Horizon::use('default');

        $this->assertSame('custom:', config('database.redis.horizon.options.prefix'));
    }
}
