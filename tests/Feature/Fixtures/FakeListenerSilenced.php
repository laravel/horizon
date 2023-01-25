<?php

namespace Laravel\Horizon\Tests\Feature\Fixtures;

use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Horizon\Contracts\Silenced;

class FakeListenerSilenced implements Silenced
{
    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var FakeEventWithModel
     */
    protected $fakeModel;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function handle(FakeEventWithModel $fakeEventWithModel): void
    {
        $this->fakeModel = $fakeEventWithModel->model;
    }
}
