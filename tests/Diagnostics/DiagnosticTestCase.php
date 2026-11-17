<?php

namespace Laravel\Horizon\Tests\Diagnostics;

use Laravel\Doctor\Doctor;
use Laravel\Doctor\DoctorServiceProvider;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\HorizonServiceProvider;
use Mockery;
use Orchestra\Testbench\TestCase;
use Throwable;

abstract class DiagnosticTestCase extends TestCase
{
    /**
     * Setup the test case.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (! class_exists(Doctor::class)) {
            $this->markTestSkipped('laravel/doctor is not installed.');
        }

        parent::setUp();
    }

    /**
     * Get the package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            HorizonServiceProvider::class,
            DoctorServiceProvider::class,
        ];
    }

    /**
     * Bind a master supervisor repository returning or throwing the given value.
     *
     * @param  array<int, object>|Throwable  $masters
     */
    protected function fakeMasters(array|Throwable $masters): void
    {
        $repository = Mockery::mock(MasterSupervisorRepository::class);

        $masters instanceof Throwable
            ? $repository->shouldReceive('all')->andThrow($masters)
            : $repository->shouldReceive('all')->andReturn($masters);

        $this->app->instance(MasterSupervisorRepository::class, $repository);
    }
}
