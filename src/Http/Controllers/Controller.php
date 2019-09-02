<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Support\Collection;
use Laravel\Horizon\Http\Middleware\Authenticate;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected $heavyFialds = ['payload', 'exception'];
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
            foreach ($this->heavyFialds as $field) {
                $item->{$field} = json_encode([]);
            }
            return $item;
        });
    }
}
