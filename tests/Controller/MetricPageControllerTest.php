<?php

namespace Laravel\Horizon\Tests\Controller;

use Inertia\Testing\AssertableInertia;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Tests\ControllerTest;
use Mockery;

class MetricPageControllerTest extends ControllerTest
{
    public function test_each_metric_type_has_its_own_inertia_page()
    {
        $metrics = Mockery::mock(MetricsRepository::class);
        // Index props and shared navigation counts both call these per request.
        $metrics->shouldReceive('measuredJobs')->andReturn(['App\\Jobs\\ProcessInvoices']);
        $metrics->shouldReceive('measuredQueues')->andReturn(['default']);
        $this->app->instance(MetricsRepository::class, $metrics);

        foreach ([
            '/horizon/metrics/jobs' => ['jobs', ['App\\Jobs\\ProcessInvoices']],
            '/horizon/metrics/queues' => ['queues', ['default']],
        ] as $url => [$type, $values]) {
            $this->actingAs(new Fakes\User)
                ->get($url)
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('metrics', false)
                    ->where('type', $type)
                    ->where('metrics', $values)
                    ->etc());
        }
    }

    public function test_job_and_queue_metrics_have_inertia_detail_pages()
    {
        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('snapshotsForJob')
            ->once()
            ->with('App\\Jobs\\Import/Orders')
            ->andReturn([
                (object) ['time' => 1784387100, 'throughput' => '19', 'runtime' => '2500'],
                (object) ['time' => 1784387400, 'throughput' => null, 'runtime' => null],
            ]);
        $metrics->shouldReceive('snapshotsForQueue')
            ->once()
            ->with('emails')
            ->andReturn([
                (object) ['time' => 1784387100, 'throughput' => '7', 'runtime' => '1250'],
            ]);
        // Shared navigation counts call measuredJobs/measuredQueues on every request.
        $metrics->shouldReceive('measuredJobs')->zeroOrMoreTimes()->andReturn([]);
        $metrics->shouldReceive('measuredQueues')->zeroOrMoreTimes()->andReturn([]);
        $this->app->instance(MetricsRepository::class, $metrics);

        foreach ([
            '/horizon/metrics/jobs/App%5CJobs%5CImport%2FOrders' => [
                'jobs',
                'App\\Jobs\\Import/Orders',
                [
                    ['timestamp' => 1784387100, 'throughput' => 19, 'runtime' => 2.5],
                    ['timestamp' => 1784387400, 'throughput' => 0, 'runtime' => null],
                ],
            ],
            '/horizon/metrics/queues/emails' => [
                'queues',
                'emails',
                [
                    ['timestamp' => 1784387100, 'throughput' => 7, 'runtime' => 1.25],
                ],
            ],
        ] as $url => [$type, $name, $snapshots]) {
            $this->actingAs(new Fakes\User)
                ->get($url)
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('metrics/show', false)
                    ->where('type', $type)
                    ->where('name', $name)
                    ->where('preview', [
                        'data' => $snapshots,
                        'available' => true,
                        'message' => null,
                    ])
                    ->etc());
        }
    }
}
