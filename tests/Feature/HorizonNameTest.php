<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Horizon;
use Laravel\Horizon\Tests\IntegrationTest;

class HorizonNameTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('horizon.name', null);
    }

    public function test_it_returns_the_horizon_name_from_config()
    {
        config()->set('horizon.name', 'my-horizon');

        $this->assertSame('my-horizon', Horizon::name());
    }

    public function test_it_returns_null_if_horizon_name_not_set()
    {
        config()->set('horizon.name', null);

        $this->assertNull(Horizon::name());
    }
}
