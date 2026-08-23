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
            ->assertSee('<style data-scheme="light">', false)
            ->assertSee('<style data-scheme="dark">', false)
            ->assertSee('<style>', false)
            ->assertSee('<script type="module">', false);
    }

    public function test_csp_nonce_is_rendered_in_style_and_script_tags_if_set()
    {
        $nonce = Str::random(40);

        Horizon::cspNonce($nonce);

        $response = $this->actingAs(new Fakes\User)
                    ->get('/horizon');

        $response->assertOk()
            ->assertSee("<style data-scheme=\"light\" nonce=\"{$nonce}\">", false)
            ->assertSee("<style data-scheme=\"dark\" nonce=\"{$nonce}\">", false)
            ->assertSee("<style nonce=\"{$nonce}\">", false)
            ->assertSee("<script type=\"module\" nonce=\"{$nonce}\">", false);
    }

    public function test_csp_nonce_value_is_escaped_when_rendered()
    {
        Horizon::cspNonce('"><script>alert(1)</script>');

        $response = $this->actingAs(new Fakes\User)
                    ->get('/horizon');

        $response->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
