<?php

namespace Laravel\Horizon\Tests;

use Mockery;
use PHPUnit_Framework_TestCase;

abstract class UnitTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }
}
