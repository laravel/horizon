<!doctype>
<html>
    <head>
        <title>Horizon</title>
        <link href="https://fonts.googleapis.com/css?family=Lato:400,700" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="{{ mix('vendor/horizon/css/app.css') }}">
        <link rel="icon" href="/vendor/horizon/img/favicon.png" />
    </head>

    <body>
        <div id="root"></div>

        <div style="height: 0; width: 0; position: absolute; display: none;">
            {!! file_get_contents(public_path('/vendor/horizon/img/sprite.svg')) !!}
        </div>

        <script src="{{ mix('vendor/horizon/js/app.js') }}"></script>
    </body>
</html>
