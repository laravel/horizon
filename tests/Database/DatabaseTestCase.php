<?php

namespace Laravel\Horizon\Tests\Database;

use Orchestra\Testbench\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    /**
     * Setup the test case.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    /**
     * Get the service providers for the package.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return ['Laravel\Horizon\HorizonServiceProvider'];
    }

    /**
     * Configure the environment.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('horizon.driver', 'database');
        $app['config']->set('horizon.database.connection', 'testing');

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $this->databaseConfig());
    }

    /**
     * Get the database connection configuration for the test suite.
     *
     * @return array
     */
    protected function databaseConfig()
    {
        switch (getenv('DB_CONNECTION') ?: 'sqlite') {
            case 'mysql':
            case 'mariadb':
                return [
                    'driver' => 'mysql',
                    'host' => getenv('DB_HOST') ?: '127.0.0.1',
                    'port' => getenv('DB_PORT') ?: '3306',
                    'database' => getenv('DB_DATABASE') ?: 'laravel',
                    'username' => getenv('DB_USERNAME') ?: 'root',
                    'password' => getenv('DB_PASSWORD') ?: '',
                    'charset' => 'utf8mb4',
                    'collation' => getenv('DB_COLLATION') ?: 'utf8mb4_unicode_ci',
                    'prefix' => '',
                ];

            case 'pgsql':
                return [
                    'driver' => 'pgsql',
                    'host' => getenv('DB_HOST') ?: '127.0.0.1',
                    'port' => getenv('DB_PORT') ?: '5432',
                    'database' => getenv('DB_DATABASE') ?: 'laravel',
                    'username' => getenv('DB_USERNAME') ?: 'forge',
                    'password' => getenv('DB_PASSWORD') ?: 'password',
                    'charset' => 'utf8',
                    'prefix' => '',
                    'schema' => 'public',
                ];

            case 'sqlsrv':
                return [
                    'driver' => 'sqlsrv',
                    'host' => getenv('DB_HOST') ?: '127.0.0.1',
                    'port' => getenv('DB_PORT') ?: '1433',
                    'database' => getenv('DB_DATABASE') ?: 'master',
                    'username' => getenv('DB_USERNAME') ?: 'SA',
                    'password' => getenv('DB_PASSWORD') ?: 'Forge123',
                    'charset' => 'utf8',
                    'prefix' => '',
                    'trust_server_certificate' => true,
                ];

            default:
                return [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ];
        }
    }
}
