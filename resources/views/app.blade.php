<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if ($cspNonce = Vite::cspNonce())
        <meta name="csp-nonce" content="{{ $cspNonce }}">
    @endif

    <x-inertia::head>
        <title>Horizon{{ config('horizon.name') ? ' - '.config('horizon.name') : '' }}</title>
    </x-inertia::head>
    @inject('assets', 'Laravel\Horizon\Assets\AssetManifest')
    <link rel="icon" href="{{ $assets->favicon() }}" type="image/svg+xml" sizes="any" data-horizon-favicon>
    <script @if ($cspNonce) nonce="{{ $cspNonce }}" @endif>
        (() => {
            try {
                const appearance = localStorage.getItem('horizonColorScheme') ?? 'system';
                const dark = appearance === 'dark'
                    || (appearance === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);

                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            } catch {
                // The dashboard remains usable when browser storage is unavailable.
            }
        })();
    </script>

    {{ $assets->tags() }}
</head>
<body>
    <x-inertia::app />
</body>
</html>
