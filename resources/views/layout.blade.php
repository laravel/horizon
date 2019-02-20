<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta Information -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('/vendor/horizon/img/favicon.png') }}">

    <title>Horizon</title>

    <!-- Style sheets-->
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link href="{{ asset(mix($cssFile, 'vendor/horizon')) }}" rel="stylesheet" type="text/css">
</head>
<body>
<div id="horizon" v-cloak>
    <alert :message="alert.message"
           :type="alert.type"
           :auto-close="alert.autoClose"
           :confirmation-proceed="alert.confirmationProceed"
           :confirmation-cancel="alert.confirmationCancel"
           v-if="alert.type"></alert>

    <div class="container mb-5">
        <div class="d-flex align-items-center py-4 header">
            <svg class="logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80">
                <path class="fill-primary" d="M0 40a39.87 39.87 0 0 1 11.72-28.28A40 40 0 1 1 0 40zm34 10a4 4 0 0 1-4-4v-2a2 2 0 1 0-4 0v2a4 4 0 0 1-4 4h-2a2 2 0 1 0 0 4h2a4 4 0 0 1 4 4v2a2 2 0 1 0 4 0v-2a4 4 0 0 1 4-4h2a2 2 0 1 0 0-4h-2zm24-24a6 6 0 0 1-6-6v-3a3 3 0 0 0-6 0v3a6 6 0 0 1-6 6h-3a3 3 0 0 0 0 6h3a6 6 0 0 1 6 6v3a3 3 0 0 0 6 0v-3a6 6 0 0 1 6-6h3a3 3 0 0 0 0-6h-3zm-4 36a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM21 28a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"></path>
            </svg>

            <h4 class="mb-0 ml-3"><strong>Laravel</strong> Horizon</h4>

            <button class="btn btn-outline-primary ml-auto" :class="{active: autoLoadsNewEntries}" v-on:click.prevent="autoLoadNewEntries" title="Auto Load Entries">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary">
                    <path d="M10 3v2a5 5 0 0 0-3.54 8.54l-1.41 1.41A7 7 0 0 1 10 3zm4.95 2.05A7 7 0 0 1 10 17v-2a5 5 0 0 0 3.54-8.54l1.41-1.41zM10 20l-4-4 4-4v8zm0-12V0l4 4-4 4z"></path>
                </svg>
            </button>
        </div>

        <div class="row mt-4">
            <div class="col-2 sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <router-link active-class="active" to="/dashboard" class="nav-link d-flex align-items-center pt-0">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M0 3c0-1.1.9-2 2-2h16a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3zm2 2v12h16V5H2zm8 3l4 5H6l4-5z"></path>
                            </svg>
                            <span>Dashboard</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/monitoring" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M7 17H2a2 2 0 0 1-2-2V2C0 .9.9 0 2 0h16a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2h-5l4 2v1H3v-1l4-2zM2 2v11h16V2H2z"></path>
                            </svg>
                            <span>Monitoring</span>
                        </router-link>
                    </li>
                </ul>
            </div>

            <div class="col-10">
                <router-view></router-view>
            </div>
        </div>
    </div>
</div>

<!-- Global Horizon Object -->
<script>
    window.Horizon = @json($horizonScriptVariables);
</script>

<script src="{{asset(mix('app.js', 'vendor/horizon'))}}"></script>
</body>
</html>
