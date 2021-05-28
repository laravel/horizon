<?php

declare(strict_types=1);

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function searchJobsByName()
    {
        return ['Kek', 'Lol', '4eburek'];
    }
}
