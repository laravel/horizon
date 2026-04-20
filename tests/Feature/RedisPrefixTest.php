<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Redis\Connections\Connection;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Tests\IntegrationTest;

class RedisPrefixTest extends IntegrationTest
{
    public function test_prefix_can_be_configured()
    {
        if (getenv('REDIS_CLUSTER_HOSTS_AND_PORTS')) {
            $this->markTestSkipped('Test is for standalone Redis connections.');
        }

        config(['horizon.prefix' => 'custom:']);

        Horizon::use('default');

        $this->assertSame('custom:', config('database.redis.horizon.options.prefix'));
    }

    public function test_cluster_connection_uses_hash_tagged_prefix()
    {
        if (! method_exists(Connection::class, 'hasHashTag')) {
            $this->markTestSkipped('Requires Laravel with Connection::hasHashTag support.');
        }

        config(['database.redis.clusters.my-cluster' => [
            ['host' => '127.0.0.1', 'port' => 6379],
        ]]);
        config(['horizon.prefix' => 'myapp_horizon:']);

        Horizon::use('my-cluster');

        $this->assertSame('{myapp_horizon:}', config('horizon.prefix'));
        $this->assertSame(
            '{myapp_horizon:}',
            config('database.redis.clusters.horizon.options.prefix')
        );
    }

    public function test_cluster_prefix_is_not_double_tagged()
    {
        if (! method_exists(Connection::class, 'hasHashTag')) {
            $this->markTestSkipped('Requires Laravel with Connection::hasHashTag support.');
        }

        config(['database.redis.clusters.my-cluster' => [
            ['host' => '127.0.0.1', 'port' => 6379],
        ]]);
        config(['horizon.prefix' => '{myapp}_horizon:']);

        Horizon::use('my-cluster');

        $this->assertSame('{myapp}_horizon:', config('horizon.prefix'));
        $this->assertSame(
            '{myapp}_horizon:',
            config('database.redis.clusters.horizon.options.prefix')
        );
    }

    public function test_standalone_connection_prefix_is_unchanged()
    {
        if (getenv('REDIS_CLUSTER_HOSTS_AND_PORTS')) {
            $this->markTestSkipped('Test is for standalone Redis connections.');
        }

        config(['horizon.prefix' => 'myapp_horizon:']);

        Horizon::use('default');

        $this->assertSame('myapp_horizon:', config('database.redis.horizon.options.prefix'));
        $this->assertSame('myapp_horizon:', config('horizon.prefix'));
    }

    public function test_cluster_connection_uses_fallback_prefix()
    {
        if (! method_exists(Connection::class, 'hasHashTag')) {
            $this->markTestSkipped('Requires Laravel with Connection::hasHashTag support.');
        }

        config(['database.redis.clusters.my-cluster' => [
            ['host' => '127.0.0.1', 'port' => 6379],
        ]]);
        config(['horizon.prefix' => '']);

        Horizon::use('my-cluster');

        $this->assertSame('{horizon:}', config('horizon.prefix'));
    }

    public function test_cluster_connection_preserves_nodes()
    {
        if (! method_exists(Connection::class, 'hasHashTag')) {
            $this->markTestSkipped('Requires Laravel with Connection::hasHashTag support.');
        }

        config(['database.redis.clusters.my-cluster' => [
            ['host' => 'node1', 'port' => 7000],
            ['host' => 'node2', 'port' => 7001],
            ['host' => 'node3', 'port' => 7002],
        ]]);
        config(['horizon.prefix' => 'horizon:']);

        Horizon::use('my-cluster');

        $this->assertSame(['host' => 'node1', 'port' => 7000], config('database.redis.clusters.horizon.0'));
        $this->assertSame(['host' => 'node2', 'port' => 7001], config('database.redis.clusters.horizon.1'));
        $this->assertSame(['host' => 'node3', 'port' => 7002], config('database.redis.clusters.horizon.2'));
    }

    public function test_use_throws_for_unknown_connection()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Redis connection [nonexistent] has not been configured.');

        Horizon::use('nonexistent');
    }

    public function test_cluster_connection_falls_back_to_standalone_when_unsupported()
    {
        // Clear any cluster horizon config set by IntegrationTest::getEnvironmentSetUp
        config(['database.redis.clusters.horizon' => null]);

        config(['database.redis.clusters.my-cluster' => [
            ['host' => '127.0.0.1', 'port' => 6379],
        ]]);
        config(['horizon.prefix' => 'myapp:']);

        HorizonWithoutClusterSupport::use('my-cluster');

        $this->assertSame('myapp:', config('database.redis.horizon.options.prefix'));
        $this->assertSame('myapp:', config('horizon.prefix'));
        $this->assertNull(config('database.redis.clusters.horizon'));
    }

    public function test_cluster_connection_registers_under_clusters_not_standalone()
    {
        if (! method_exists(Connection::class, 'hasHashTag')) {
            $this->markTestSkipped('Requires Laravel with Connection::hasHashTag support.');
        }

        config(['database.redis.clusters.my-cluster' => [
            ['host' => '127.0.0.1', 'port' => 6379],
        ]]);
        config(['horizon.prefix' => 'horizon:']);

        Horizon::use('my-cluster');

        $this->assertNotNull(config('database.redis.clusters.horizon'));
    }

    public function test_standalone_connection_does_not_register_under_clusters()
    {
        if (getenv('REDIS_CLUSTER_HOSTS_AND_PORTS')) {
            $this->markTestSkipped('Test is for standalone Redis connections.');
        }

        config(['horizon.prefix' => 'horizon:']);

        Horizon::use('default');

        $this->assertNotNull(config('database.redis.horizon'));
    }

    public function test_cluster_connection_preserves_existing_options()
    {
        if (! method_exists(Connection::class, 'hasHashTag')) {
            $this->markTestSkipped('Requires Laravel with Connection::hasHashTag support.');
        }

        config(['database.redis.clusters.my-cluster' => [
            ['host' => '127.0.0.1', 'port' => 6379],
            'options' => ['timeout' => 5],
        ]]);
        config(['horizon.prefix' => 'horizon:']);

        Horizon::use('my-cluster');

        $this->assertSame(5, config('database.redis.clusters.horizon.options.timeout'));
        $this->assertSame('{horizon:}', config('database.redis.clusters.horizon.options.prefix'));
    }

    public function test_cluster_takes_precedence_over_standalone_with_same_name()
    {
        if (! method_exists(Connection::class, 'hasHashTag')) {
            $this->markTestSkipped('Requires Laravel with Connection::hasHashTag support.');
        }

        config(['database.redis.clusters.shared' => [
            ['host' => '127.0.0.1', 'port' => 6379],
        ]]);
        config(['database.redis.shared' => [
            'host' => '127.0.0.1', 'port' => 6379,
        ]]);
        config(['horizon.prefix' => 'horizon:']);

        Horizon::use('shared');

        $this->assertNotNull(config('database.redis.clusters.horizon'));
    }
}

class HorizonWithoutClusterSupport extends Horizon
{
    protected static function supportsClustering(): bool
    {
        return false;
    }
}
