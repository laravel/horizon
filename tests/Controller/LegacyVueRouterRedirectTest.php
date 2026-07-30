<?php

namespace Laravel\Horizon\Tests\Controller;

use Illuminate\Support\Facades\Route;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Support\HorizonUrl;
use Laravel\Horizon\Tests\ControllerTest;

class LegacyVueRouterRedirectTest extends ControllerTest
{
    public function test_metrics_redirects_to_the_jobs_metrics_page()
    {
        $this->actingAs(new Fakes\User)
            ->get('/horizon/metrics')
            ->assertRedirect('/horizon/metrics/jobs')
            ->assertStatus(302);
    }

    public function test_monitoring_tag_redirects_to_the_tag_jobs_page()
    {
        $this->actingAs(new Fakes\User)
            ->get('/horizon/monitoring/mail')
            ->assertRedirect('/horizon/monitoring/mail/jobs')
            ->assertStatus(302);
    }

    public function test_failed_index_redirects_to_the_failed_jobs_page()
    {
        $this->actingAs(new Fakes\User)
            ->get('/horizon/failed')
            ->assertRedirect('/horizon/jobs/failed')
            ->assertStatus(302);
    }

    public function test_failed_job_redirects_to_the_failed_job_detail_page()
    {
        $this->actingAs(new Fakes\User)
            ->get('/horizon/failed/job-123')
            ->assertRedirect('/horizon/jobs/failed/job-123')
            ->assertStatus(302);
    }

    public function test_dynamic_parameters_are_encoded_in_redirect_targets()
    {
        $tag = 'App\\Jobs\\SendInvoice';
        $jobId = 'job with spaces';

        $this->actingAs(new Fakes\User)
            ->get('/horizon/monitoring/'.rawurlencode($tag))
            ->assertRedirect('/horizon/monitoring/'.rawurlencode($tag).'/jobs');

        $this->actingAs(new Fakes\User)
            ->get('/horizon/failed/'.rawurlencode($jobId))
            ->assertRedirect('/horizon/jobs/failed/'.rawurlencode($jobId));
    }

    public function test_incoming_query_string_is_preserved()
    {
        $failed = $this->actingAs(new Fakes\User)
            ->get('/horizon/failed?tag=mail&page=2')
            ->assertStatus(302);

        $this->assertEqualsCanonicalizing(
            ['tag' => 'mail', 'page' => '2'],
            $this->queryParameters($failed->headers->get('Location')),
        );

        $this->actingAs(new Fakes\User)
            ->get('/horizon/metrics?tab=throughput')
            ->assertRedirect('/horizon/metrics/jobs?tab=throughput');
    }

    public function test_proxy_path_is_prefixed_on_redirect_locations()
    {
        $this->app['config']->set('horizon.proxy_path', 'gateway');

        $this->actingAs(new Fakes\User)
            ->get('/horizon/metrics')
            ->assertRedirect('/gateway/horizon/metrics/jobs');

        $this->actingAs(new Fakes\User)
            ->get('/horizon/failed/job-9')
            ->assertRedirect('/gateway/horizon/jobs/failed/job-9');
    }

    public function test_horizon_url_helper_respects_path_and_proxy_configuration()
    {
        // path is baked into named routes at boot (default "horizon"); proxy is applied live.
        $this->assertSame(
            '/horizon/metrics/jobs',
            HorizonUrl::route('horizon.metrics.page', ['type' => 'jobs']),
        );

        $this->app['config']->set('horizon.proxy_path', '/ops/');

        $this->assertSame(
            '/ops/horizon/jobs/failed',
            HorizonUrl::route('horizon.failed-jobs.page'),
        );
    }

    public function test_legacy_redirects_are_protected_by_horizon_authorization()
    {
        Horizon::auth(fn () => false);

        foreach ([
            '/horizon/metrics',
            '/horizon/monitoring/mail',
            '/horizon/failed',
            '/horizon/failed/job-1',
        ] as $url) {
            $this->actingAs(new Fakes\User)
                ->get($url)
                ->assertForbidden();
        }
    }

    public function test_legacy_redirects_are_not_registered_as_named_routes()
    {
        $this->assertFalse(Route::has('horizon.metrics.legacy'));
        $this->assertFalse(Route::has('horizon.failed.legacy'));

        $named = collect(Route::getRoutes())->map->getName()->filter()->values();

        $this->assertFalse($named->contains(fn (?string $name) => is_string($name) && str_contains($name, 'legacy')));
    }

    public function test_explicit_monitoring_and_failed_pages_are_not_shadowed()
    {
        // Sanity: more-specific paths still resolve as named Inertia destinations, not legacy redirects.
        $this->assertSame(
            url('/horizon/monitoring/mail/jobs'),
            route('horizon.monitoring-jobs.page', ['tag' => 'mail']),
        );
        $this->assertSame(
            url('/horizon/jobs/failed'),
            route('horizon.failed-jobs.page'),
        );
    }

    /**
     * @return array<string, string>
     */
    private function queryParameters(string $location): array
    {
        $query = parse_url($location, PHP_URL_QUERY) ?: '';
        parse_str($query, $parameters);

        /** @var array<string, string> $parameters */
        return $parameters;
    }
}
