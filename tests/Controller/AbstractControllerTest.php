<?php

namespace Laravel\Horizon\Tests\Controller;

use Laravel\Horizon\Horizon;
use Laravel\Horizon\Tests\IntegrationTest;

abstract class AbstractControllerTest extends IntegrationTest
{
    public function setUp()
    {
        parent::setUp();

        Horizon::auth(function () {
            return true;
        });
    }
}
