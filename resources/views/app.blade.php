<!doctype>
<html>
    <head>
        <title>Horizon</title>
        <link href="https://fonts.googleapis.com/css?family=Lato:400,700" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="{{ config('horizon.base_path') . mix('css/app.css', 'vendor/horizon') }}">
        <link rel="icon" href="/vendor/horizon/img/favicon.png" />
        <script>
            window.basePath = '{{ config('horizon.base_path') }}'
        </script>
    </head>

    <body>
        <div id="root"></div>

        <div style="height: 0; width: 0; position: absolute; display: none;">
            {!! file_get_contents(public_path('/vendor/horizon/img/sprite.svg')) !!}
        </div>

        <script src="{{ config('horizon.base_path') . mix('js/app.js', 'vendor/horizon') }}"></script>
    </body>
</html>
