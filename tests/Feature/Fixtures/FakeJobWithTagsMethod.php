<?php

namespace Laravel\Horizon\Tests\Feature\Fixtures;

class FakeJobWithTagsMethod
{
    public function tags()
    {
        return [
            'first',
            'second',
        ];
    }
}
