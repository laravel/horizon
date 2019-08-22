<?php

namespace Laravel\Horizon\Tests;

use Mockery;
use PHPUnit\Framework\TestCase;

abstract class UnitTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }
}
