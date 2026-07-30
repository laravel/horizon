<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Foundation\Vite;
use Illuminate\Support\Str;
use Laravel\Horizon\Assets\AssetManifest;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Tests\ControllerTest;

class CspNonceTest extends ControllerTest
{
    public function test_csp_nonce_is_not_rendered_when_not_set()
    {
        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertOk()
            ->assertSee('data-horizon-inertia', false)
            ->assertDontSee('meta name="csp-nonce"', false)
            ->assertDontSee('nonce="', false);
    }

    public function test_horizon_csp_nonce_delegates_to_laravel_vite_and_marks_dashboard_assets()
    {
        $nonce = Str::random(40);

        Horizon::cspNonce($nonce);

        $this->assertSame($nonce, app(Vite::class)->cspNonce());

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertOk()
            ->assertSee("<meta name=\"csp-nonce\" content=\"{$nonce}\">", false)
            ->assertSee("nonce=\"{$nonce}\"", false)
            ->assertSee('data-horizon-inertia', false);

        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/<script[^>]*nonce="'.preg_quote($nonce, '/').'"[^>]*>[\s\S]*horizonColorScheme/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/data-horizon-inertia[^>]*nonce="'.preg_quote($nonce, '/').'"|nonce="'.preg_quote($nonce, '/').'"[^>]*data-horizon-inertia/',
            $html,
        );
    }

    public function test_laravel_vite_csp_nonce_is_honored_without_horizon_helper()
    {
        $nonce = Str::random(40);

        app(Vite::class)->useCspNonce($nonce);

        $response = $this->actingAs(new Fakes\User)
            ->get('/horizon');

        $response->assertOk()
            ->assertSee("<meta name=\"csp-nonce\" content=\"{$nonce}\">", false)
            ->assertSee("nonce=\"{$nonce}\"", false)
            ->assertSee('data-horizon-inertia', false);

        $this->assertMatchesRegularExpression(
            '/data-horizon-inertia[^>]*nonce="'.preg_quote($nonce, '/').'"|nonce="'.preg_quote($nonce, '/').'"[^>]*data-horizon-inertia/',
            $response->getContent(),
        );
    }

    public function test_dev_server_tags_receive_csp_nonce()
    {
        $nonce = Str::random(40);

        config(['horizon.vite_dev_server' => 'https://horizon-v2-vite.nmbp']);
        app(Vite::class)->useCspNonce($nonce);

        $tags = app(AssetManifest::class)->tags()->toHtml();

        $this->assertStringContainsString('nonce="'.$nonce.'"', $tags);
        $this->assertStringContainsString('https://horizon-v2-vite.nmbp/@vite/client', $tags);
        $this->assertStringContainsString('@react-refresh', $tags);
        $this->assertStringContainsString(
            'https://horizon-v2-vite.nmbp/resources/js/app.tsx',
            $tags,
        );
    }
}
