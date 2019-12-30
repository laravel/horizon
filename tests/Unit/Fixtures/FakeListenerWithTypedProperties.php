<?php

namespace Laravel\Horizon\Tests\Unit\Fixtures;

use Illuminate\Contracts\Events\Dispatcher;

class FakeListenerWithTypedProperties
{
    protected Dispatcher $dispatcher;

    protected FakeEventWithModel $fakeModel;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function handle(FakeEventWithModel $fakeEventWithModel): void
    {
        $this->fakeModel = $fakeEventWithModel->model;
    }
}
