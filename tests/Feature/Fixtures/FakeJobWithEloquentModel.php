<?php

namespace Laravel\Horizon\Tests\Feature\Fixtures;

class FakeJobWithEloquentModel
{
    public $nonModel;
    public $first;
    public $second;

    public function __construct($first, $second)
    {
        $this->nonModel = 1;
        $this->first = $first;
        $this->second = $second;
    }
}
