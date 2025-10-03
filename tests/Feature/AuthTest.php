<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Http\Request;
use Laravel\Horizon\Exceptions\ForbiddenException;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Http\Middleware\Authenticate;
use Laravel\Horizon\Tests\IntegrationTest;
use Mockery as m;

class AuthTest extends IntegrationTest
{
    public function test_authentication_callback_works()
    {
        $request = Request::create('/', 'GET', ['name' => 'taylor']);

        $this->assertFalse(Horizon::check($request));

        Horizon::auth(function ($request) {
            return $request->input('name') === 'taylor';
        });

        $this->assertTrue(Horizon::check($request));
        $this->assertFalse(Horizon::check(Request::create('/', 'GET')));
    }

    public function test_authentication_middleware_can_pass()
    {
        Horizon::auth(function () {
            return true;
        });

        $middleware = new Authenticate;

        $response = $middleware->handle(
            m::mock(Request::class),
            function ($value) {
                return 'response';
            }
        );

        $this->assertSame('response', $response);
    }

    public function test_authentication_middleware_throws_on_failure()
    {
        $this->expectException(ForbiddenException::class);

        Horizon::auth(function () {
            return false;
        });

        $middleware = new Authenticate;

        $middleware->handle(
            m::mock(Request::class),
            function ($value) {
                return 'response';
            }
        );
    }
}
