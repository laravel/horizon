<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Horizon;
use Laravel\Horizon\Tests\IntegrationTest;
use Throwable;

class UsingConnectionTest extends IntegrationTest
{
    public function testCanUseDifferentConnection()
    {
        Horizon::use('default');

        $secondConfig = config('database.redis.default');
        $secondConfig['database'] = '100';

        config(['database.redis.test' => $secondConfig]);

        Horizon::usingConnection('test', function() {
            $this->assertSame('100', config('database.redis.horizon.database'));
            $this->assertSame('custom:', config('database.redis.horizon.options.prefix'));
        }, 'custom:');

        $this->assertSame('0', config('database.redis.horizon.database'));
    }

    public function testInvalidConnectionThrowsExceptionAndResetsConfiguration()
    {
        Horizon::use('default', $prefix = 'my_prefix:');

        $initialConfig = config('database.redis.horizon');
        try {
            Horizon::usingConnection('test', function () {
                $this->assertSame('100', config('database.redis.horizon.database'));
                $this->assertSame('custom:', config('database.redis.horizon.options.prefix'));
            });
        } catch (Throwable $exception) {
            $this->assertEquals("Redis connection [test] has not been configured.", $exception->getMessage());
        }

        $this->assertEqualsCanonicalizing($initialConfig, config('database.redis.horizon'));
        $this->assertSame($prefix, config('database.redis.horizon.options.prefix'));
    }
}
