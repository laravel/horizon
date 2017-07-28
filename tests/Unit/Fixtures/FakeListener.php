<?php

namespace Laravel\Horizon\Tests\Unit\Fixtures;

class FakeListener
{
    public function tags()
    {
        return [
            'listenerTag1',
            'listenerTag2',
        ];
    }
}
