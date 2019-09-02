<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Support\Collection;
use Laravel\Horizon\Http\Middleware\Authenticate;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(Authenticate::class);
    }

    protected function filterHeavyFields(Collection $collection)
    {
        return $collection->transform(function ($item, $key) {
            $item->payload->data = [];
            $item->exception = [];
            return $item;
        });
    }
}
