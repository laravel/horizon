<?php

namespace Laravel\Horizon\Tests\Unit\Fixtures;

class FakeEventWithModel
{
    public $model;

    public function __construct($id)
    {
        $this->model = new FakeModel;
        $this->model->id = $id;
    }
}
