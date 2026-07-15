<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Str;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Tests\ControllerTest;

class CspNonceTest extends ControllerTest
{
    public function test_csp_nonce_is_not_rendered_in_style_and_script_tags_if_not_set()
    {
        $response = $this->actingAs(new Fakes\User)
                    ->get('/horizon');

        $response->assertOk()
            ->assertSeeHtml('<style data-scheme="light">')
            ->assertSeeHtml('<style data-scheme="dark">')
            ->assertSeeHtml('<style>')
            ->assertSeeHtml('<script type="module">');
    }

    public function test_csp_nonce_is_rendered_in_style_and_script_tags_if_set()
    {
        $nonce = Str::random(40);

        Horizon::cspNonce($nonce);

        $response = $this->actingAs(new Fakes\User)
                    ->get('/horizon');

        $response->assertOk()
            ->assertSeeHtml("<style data-scheme=\"light\" nonce=\"{$nonce}\">")
            ->assertSeeHtml("<style data-scheme=\"dark\" nonce=\"{$nonce}\">")
            ->assertSeeHtml("<style nonce=\"{$nonce}\">")
            ->assertSeeHtml("<script type=\"module\" nonce=\"{$nonce}\">");
    }
}
