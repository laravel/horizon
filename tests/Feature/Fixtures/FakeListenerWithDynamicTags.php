<?php

namespace Laravel\Horizon\Tests\Feature\Fixtures;

class FakeListenerWithDynamicTags
{
    public function tags(FakeEvent $event)
    {
        return [
            'listenerTag1',
            get_class($event),
        ];
    }
}
