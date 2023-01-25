<?php

namespace Laravel\Horizon\Tests\Feature\Fixtures;

class FakeEvent
{
    public function tags()
    {
        return [
            'eventTag1',
            'eventTag2',
        ];
    }
}
