<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Tests\ControllerTest;

class DashboardAssetsTest extends ControllerTest
{
    public function test_dashboard_does_not_load_fonts_from_an_external_host()
    {
        $response = $this->actingAs(new Fakes\User)
                    ->get('/horizon');

        $response->assertOk()
            ->assertDontSee('fonts.bunny.net', false)
            ->assertDontSee('fonts.googleapis.com', false);
    }
}
