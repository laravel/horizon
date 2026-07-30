<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Bus\BatchRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Inertia\Ssr\HttpGateway;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Http\Middleware\HandleInertiaRequests;
use Laravel\Horizon\Tests\ControllerTest;
use Mockery;

class HorizonPublicApiTest extends ControllerTest
{
    public function test_horizon_index_route_is_registered_for_the_dashboard()
    {
        $this->assertTrue(Route::has('horizon.index'));
        $this->assertTrue(Route::has('horizon.dashboard'));

        $this->assertSame(url('/horizon'), route('horizon.index'));
        $this->assertSame(url('/horizon/dashboard'), route('horizon.dashboard'));
    }

    public function test_dashboard_shell_uses_published_build_assets()
    {
        $this->mockEmptyBatchRepository();

        $response = $this->actingAs(new Fakes\User)
            ->get(route('horizon.index'));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringNotContainsString('window.Horizon', $html);
        $this->assertStringContainsString('data-horizon-inertia', $html);
        $this->assertStringContainsString('data-horizon-favicon', $html);
        $this->assertStringContainsString('vendor/horizon/build', $html);
        $this->assertMatchesRegularExpression('#/vendor/horizon/build/[^"\']+\.css#', $html);
        $this->assertMatchesRegularExpression('#/vendor/horizon/build/[^"\']+\.js#', $html);
        $this->assertMatchesRegularExpression('#/vendor/horizon/build/assets/[^"\']+\.svg#', $html);
    }

    public function test_dashboard_root_view_renders_inertia_fallback_title_and_app_without_csrf_meta()
    {
        $this->mockEmptyBatchRepository();

        $response = $this->actingAs(new Fakes\User)
            ->get(route('horizon.index'));

        $response->assertOk()
            ->assertSee('id="app"', false)
            ->assertSee('data-page="app"', false)
            ->assertDontSee('csrf-token', false)
            ->assertDontSee('meta name="csp-nonce"', false)
            ->assertDontSee('data:image/png;base64,', false)
            ->assertSee('data-horizon-favicon', false)
            ->assertSee('type="image/svg+xml"', false);

        $this->assertMatchesRegularExpression(
            '/<title>Horizon(?: - .+)?<\/title>/',
            $response->getContent(),
        );
    }

    public function test_dashboard_requests_are_excluded_from_consumer_ssr()
    {
        $this->mockEmptyBatchRepository();

        config([
            'inertia.ssr.enabled' => true,
            'inertia.ssr.ensure_bundle_exists' => false,
            'inertia.ssr.url' => 'http://127.0.0.1:13714',
        ]);

        Http::fake([
            'http://127.0.0.1:13714/*' => Http::response([
                'head' => [],
                'body' => '<div id="app" data-server-rendered="true">ssr</div>',
            ], 200),
        ]);

        $response = $this->actingAs(new Fakes\User)
            ->get(route('horizon.index'));

        $response->assertOk()
            ->assertSee('id="app"', false)
            ->assertSee('data-page="app"', false);

        Http::assertNothingSent();

        $ssr = app(HttpGateway::class)->dispatch([
            'component' => 'Consumer',
            'props' => [],
            'url' => '/consumer',
            'version' => '',
        ], Request::create('/consumer', 'GET'));

        $this->assertNotNull($ssr);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'http://127.0.0.1:13714/render');
    }

    public function test_root_mounted_horizon_ssr_exclusions_do_not_cover_unrelated_routes()
    {
        config([
            'horizon.path' => '',
            'horizon.proxy_path' => 'gateway',
            'inertia.ssr.enabled' => true,
            'inertia.ssr.ensure_bundle_exists' => false,
            'inertia.ssr.url' => 'http://127.0.0.1:13714',
        ]);

        Http::fake([
            'http://127.0.0.1:13714/*' => Http::response([
                'head' => [],
                'body' => '<div id="app" data-server-rendered="true">ssr</div>',
            ], 200),
        ]);

        $this->app->make(HandleInertiaRequests::class)
            ->handle(Request::create('/dashboard', 'GET'), fn () => response('ok'));

        $page = [
            'component' => 'Page',
            'props' => [],
            'url' => '/',
            'version' => '',
        ];

        $this->assertNull(
            app(HttpGateway::class)->dispatch($page, Request::create('/dashboard', 'GET')),
        );
        $this->assertNull(
            app(HttpGateway::class)->dispatch($page, Request::create('/gateway/dashboard', 'GET')),
        );
        $this->assertNull(
            app(HttpGateway::class)->dispatch($page, Request::create('/jobs/pending', 'GET')),
        );
        $this->assertNull(
            app(HttpGateway::class)->dispatch($page, Request::create('/gateway/jobs/pending', 'GET')),
        );
        $this->assertNull(
            app(HttpGateway::class)->dispatch($page, Request::create('/monitoring/foo/jobs', 'GET')),
        );
        $this->assertNull(
            app(HttpGateway::class)->dispatch($page, Request::create('/gateway/monitoring/foo/jobs', 'GET')),
        );

        Http::assertNothingSent();

        $consumer = app(HttpGateway::class)->dispatch(
            [...$page, 'component' => 'Consumer', 'url' => '/consumer'],
            Request::create('/consumer', 'GET'),
        );

        $this->assertNotNull($consumer);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'http://127.0.0.1:13714/render');
    }

    public function test_inertia_version_uses_published_manifest_hash()
    {
        $version = Horizon::inertiaVersion();

        $this->assertNotSame('', $version);
        $this->assertSame(32, strlen($version));
        $this->assertSame($version, Horizon::inertiaVersion());
    }

    private function mockEmptyBatchRepository(): void
    {
        $batches = Mockery::mock(BatchRepository::class);
        $batches->shouldReceive('get')->with(51, null)->andReturn([]);
        $this->app->instance(BatchRepository::class, $batches);
    }
}
