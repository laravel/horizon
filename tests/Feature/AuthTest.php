<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Horizon;
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

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function test_authentication_middleware_responds_with_403_on_failure()
    {
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
}
