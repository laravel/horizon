<?php

namespace Laravel\Horizon\Tests;

use Mockery;
use PHPUnit\Framework\TestCase;

abstract class UnitTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }
}
