<?php

namespace Laravel\Horizon\Tests;

use Laravel\Horizon\Horizon;
use Orchestra\Testbench\Attributes\WithConfig;

#[WithConfig('app.key', 'base64:UTyp33UhGolgzCK5CJmT+hNHcA+dJyp3+oINtX+VoPI=')]
abstract class ControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        Horizon::auth(function () {
            return true;
        });
    }
}
