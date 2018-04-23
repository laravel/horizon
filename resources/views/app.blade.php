<!doctype>
<html>
    <head>
        <title>Horizon</title>
        <link href="https://fonts.googleapis.com/css?family=Lato:400,700" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="{{ mix('css/app.css', 'vendor/horizon') }}">
        <link rel="icon" href="/vendor/horizon/img/favicon.png" />
        <script type="text/javascript">
            window.HORIZON = {
                logo_url: '{{e(config('horizon.logo_url'))}}'
            };

        </script>
    </head>

    <body>
        <div id="root"></div>

        <div style="height: 0; width: 0; position: absolute; display: none;">
            {!! file_get_contents(public_path('/vendor/horizon/img/sprite.svg')) !!}
        </div>

        <script src="{{ mix('js/app.js', 'vendor/horizon') }}"></script>
    </body>
</html>
