<?php

namespace Laravel\Horizon;

function normalize_url(?string $url): string 
{
    if (is_null($url)) {
        return '/';
    }

    return rtrim($url, '/').'/';
}