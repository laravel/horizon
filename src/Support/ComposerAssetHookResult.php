<?php

namespace Laravel\Horizon\Support;

enum ComposerAssetHookResult
{
    case Added;
    case AlreadyPresent;
    case Missing;
    case Malformed;
    case Failed;
}
