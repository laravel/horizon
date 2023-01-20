<?php

namespace Laravel\Horizon\Tests\Unit\Fixtures;

class FakeJobWithSilencedMethod
{
    public function silenced()
    {
        return true;
    }
}
