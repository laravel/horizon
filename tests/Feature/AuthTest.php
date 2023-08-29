<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Horizon;
use Laravel\Horizon\Http\Middleware\Authenticate;
use Laravel\Horizon\Tests\IntegrationTest;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthTest extends IntegrationTest
{
    public function test_authentication_callback_works()
    {
        $this->assertFalse(Horizon::check('taylor'));

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

        $middleware = new Authenticate;

        $response = $middleware->handle(
            new class {
            },
            function ($value) {
                return 'response';
            }
        );

        $this->assertSame('response', $response);
    }

    public function test_authentication_middleware_responds_with_403_on_failure()
    {
        $this->expectException(HttpException::class);

        Horizon::auth(function () {
            return false;
        });

        $middleware = new Authenticate;

        $middleware->handle(
            new class {
            },
            function ($value) {
                return 'response';
            }
        );
    }

    public function test_it_can_use_a_custom_status_code(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->app['config']->set('horizon.unauthorized_status', 404);

        $middleware = new Authenticate;

        $middleware->handle(
            new class {
            },
            function ($value) {
                return 'response';
            }
        );
    }
}
