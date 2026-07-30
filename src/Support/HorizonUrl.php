<?php

declare(strict_types=1);

namespace Laravel\Horizon\Support;

/**
 * Build configuration-aware Horizon dashboard paths for server-side redirects.
 *
 * Named routes already include config('horizon.path'); proxy_path is applied at
 * generation time so reverse-proxy mounts receive a browser-usable Location.
 */
final class HorizonUrl
{
    /**
     * Absolute-path URL for a named Horizon route, including proxy_path when set.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function route(string $name, array $parameters = []): string
    {
        $path = route($name, $parameters, absolute: false);
        $proxy = trim((string) config('horizon.proxy_path', ''), '/');

        if ($proxy === '') {
            return $path;
        }

        return '/'.$proxy.($path === '/' ? '' : $path);
    }
}
