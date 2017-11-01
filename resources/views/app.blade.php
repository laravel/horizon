<!doctype>
<html>
    <head>
        <title>Horizon</title>
        <link href="https://fonts.googleapis.com/css?family=Lato:400,700" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="{{ config('horizon.base_path') . mix('css/app.css', 'vendor/horizon') }}">
    </head>

    <body>
        <div id="root">
            <test rootdirectory="test"></test>
        </div>

        <div style="height: 0; width: 0; position: absolute; display: none;">
            {!! file_get_contents(public_path('/vendor/horizon/img/sprite.svg')) !!}
        </div>

        <script>
            window.basePath = '{{ config('horizon.base_path') . '/' }}'
        </script>
        <script src="{{ config('horizon.base_path') . mix('js/app.js', 'vendor/horizon') }}"></script>
    </body>
</html>
