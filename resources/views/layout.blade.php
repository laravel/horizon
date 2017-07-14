<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Laravel Horizon</title>

    <!-- Horizon UI CSS -->
    <style>
        {!! file_get_contents(HORIZON_PATH.'/resources/dist/css/app.css'); !!}
    </style>
</head>

<body>
    <div id="app">
        <example></example>
    </div>

    <!-- Horizon UI JavaScript -->
    <script>
        {!! file_get_contents(HORIZON_PATH.'/resources/dist/js/app.js'); !!}
    </script>
</body>
</html>
