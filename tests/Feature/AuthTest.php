<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Http\Request;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Tests\Feature\Fixtures\CustomAuthMiddleware;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\Http\Middleware\Authenticate;

class AuthTest extends IntegrationTest
{
    public function test_authentication_callback_works()
    {
        $this->assertTrue(Horizon::check('taylor'));

        Horizon::auth(function ($request) {
            return $request === 'taylor';
        });

        $this->assertTrue(Horizon::check('taylor'));
        $this->assertFalse(Horizon::check('adam'));
        $this->assertFalse(Horizon::check(null));
    }

    public function test_authentication_middleware_can_pass()
    {
        Horizon::auth(function () {
            return true;
        });

        $middleware = Horizon::middleware();
        $middleware = new $middleware;

        $response = $middleware->handle(
            new class {
            },
            function ($value) {
                return 'response';
            }
        );

        $this->assertSame('response', $response);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function test_authentication_middleware_responds_with_403_on_failure()
    {
        Horizon::auth(function () {
            return false;
        });

        $middleware = Horizon::middleware();
        $middleware = new $middleware;

        $middleware->handle(
            new class {
            },
            function ($value) {
                return 'response';
            }
        );
    }

    public function test_custom_middleware_can_be_specified()
    {
        Horizon::auth(CustomAuthMiddleware::class);

        $middleware = Horizon::middleware();
        $middleware = new $middleware;

        $response = $middleware->handle(
            Request::create('/horizon', 'GET', ['passes' => true]),
            function ($request) {
                return 'response';
            }
        );

        $this->assertSame('response', $response);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function test_custom_middleware_failure_works()
    {
        Horizon::auth(CustomAuthMiddleware::class);

        $middleware = Horizon::middleware();
        $middleware = new $middleware;

        $middleware->handle(
            Request::create('/horizon', 'GET', ['passes' => false]),
            function ($request) {
                return 'response';
            }
        );
    }

    /**
     * @expectedException \Laravel\Horizon\Contracts\InvalidAuthenticationMethod
     */
    public function test_horizon_check_does_not_work_with_custom_middleware()
    {
        Horizon::auth(CustomAuthMiddleware::class);

        Horizon::check(Request::create('/horizon'));
    }
}
