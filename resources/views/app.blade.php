<!doctype>
<html>
    <head>
        <title>Horizon</title>
        <link href="https://fonts.googleapis.com/css?family=Lato:400,700" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="{{ asset('/vendor/horizon/css/app.css') }}">
    </head>

    <body>
        <div id="root"></div>

        <div style="height: 0; width: 0; position: absolute; visibility: hidden;">
            {!! file_get_contents(public_path('/vendor/horizon/img/sprite.svg')) !!}
        </div>

        <script src="{{ asset('/vendor/horizon/js/app.js') }}"></script>
    </body>
</html>
