<!doctype>
<html>
    <head>
        <title>Horizon</title>
        <link rel="stylesheet" href="{{ mix('vendor/horizon/css/app.css') }}">
        <link rel="icon" href="/vendor/horizon/img/favicon.png"/>
        <meta name="logo" content="/vendor/horizon/img/horizon.svg">
    </head>

    <body>
        <div id="root"></div>

        <div style="height: 0; width: 0; position: absolute; display: none;">
            {!! file_get_contents(public_path('/vendor/horizon/img/sprite.svg')) !!}
        </div>

        <footer id="mainFooter" class="pt-4 pb-4 text-center">
            Laravel is a trademark of Taylor Otwell. Copyright © Laravel LLC. All rights reserved.
        </footer>

        <script src="{{ mix('vendor/horizon/js/app.js') }}"></script>
    </body>
</html>
