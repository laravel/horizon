<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\JobId;
use Laravel\Horizon\Tests\IntegrationTest;

class JobIdTest extends IntegrationTest
{
    public function test_ids_can_be_generated()
    {
        $this->assertEquals('1', JobId::generate());
        $this->assertEquals('2', JobId::generate());
        $this->assertEquals('3', JobId::generate());
    }


    public function test_custom_ids_can_be_generated()
    {
        JobId::generateUsing(function () {
            return 'foo';
        });

        $this->assertEquals('foo', JobId::generate());

        JobId::generateUsing(null);
    }
}
