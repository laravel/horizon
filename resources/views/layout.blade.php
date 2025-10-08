<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta Information -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAipJREFUeNrEV8txwjAQtQ2HHCmB3JKbSQOYCoA0gD0pgFBBwpEToQAGKmDglpwgFdg5kZtNB1BBsuusZ4RY2ZZjYGd2jGWh97Q/rUwjpziPT3V4dECboDZoXZoSka5Al5vFNMqzrpkD2IFHn8B1ZAM6BCKbQgQAuAaPWQFgjoinsoipAEcTr0FrRjmyJxLLTAI5wXFXAehBGMPYcDKIIIm5kkAGOJpwAjqHRfYpbkOXvTBBypIwpT+HCvA3Cqi9Rta8EhHOHS1YCy1oWMKHmQIcGQ90wGMfLaZIoEGAoiDGOHmxhFTr5PGZJgncZYszEGC6ogX6nNn/Ay6RGDCfYveYVOFCJuAaumbPiIk1kyUNS2H6SZngyZrMWM+i/JVlXjK4QUVI3pRTpYPlaG6yeyGvm0Jef1ItiArwQBKu8G5bTMEIhKLkU3q65D+HgieE7+MCBHbygMVMOlCK+CnVDOUZ5s00ghCt2T45C+DDD2MBW/O066YFLYGvuXU5C9i6GYaLUzqr+olQtS5aIMwwtW6QfQnv7awNVanolEWgo9nABBb1cNeSmMDyigRWZkqdPrdEkDm3SRYMr7D7odwRXdIK8e7lOuAxh8W5pHtSiOhw8S4A7iX9IErlyC5b/7t+/7Ar4TKiEuyyRuJA5cQ5Wz8gEhgPNyXvfCQPVtgI+SPxAT/vSqiSEbXh70Uvp27GRSMNeJjV2Jp5V6MGpUeuUR0wAemKuwdy8ivAAJcc0R2NFxWtAAAAAElFTkSuQmCC">

    <!-- Style sheets-->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600" rel="stylesheet" />
    {{ Laravel\Horizon\Horizon::css() }}
    {{ Laravel\Horizon\Horizon::js() }}
</head>
<body>
    @inertia
</body>
</html>
