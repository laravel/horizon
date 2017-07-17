<p align="center"><img src="https://laravel.com/assets/img/components/logo-horizon.svg"></p>

## Introduction

Horizon provides a beautiful dashboard and code-driven configuration for your Laravel powered Redis queues. Horizon allows you to easily monitor key metrics of your queue system such as job throughput, runtime, and job failures.

All of your worker configuration is stored in a single, simple configuration file, allowing your configuration to stay in source control where your entire team can collaborate.

## Installation

> **Note:** Horizon is currently in beta.

Horizon requires Laravel 5.5, which is currently in beta, and PHP 7.1+. You may use Composer to install Horizon into your Laravel project:

    composer require laravel/horizon

After installing Horizon, publish its assets using the `vendor:publish` Artisan command:

    php artisan vendor:publish

## Configuration

After publishing Horizon's assets, its primary configuration file will be located at `config/horizon.php`. This configuration file allows you to configure your worker options and each configuration option includes a description of its purpose, so be sure to thoroughly explore this file.

### Web Dashboard Authentication

Horizon exposes a dashboard at `/horizon`. By default, you will only be able to access this dashboard in the `local` environment. To define a more specific access policy for the dashboard, you should use the `Horizon::auth` method. The `auth` method accepts a callback which should return `true` or `false`, indicating whether the user should have access to the Horizon dashboard:

```php
Horizon::auth(function ($request) {
    // return true / false;
});
```

## Running Horizon

Once you have configured your workers in the `config/horizon.php` configuration file, you may start Horizon using the `horizon` Artisan command. This single command will start all of your configured workers:

    php artisan horizon

You may pause the Horizon process and instruct it to continue processing jobs using the `horizon:pause` and `horizon:continue` Artisan commands:

    php artisan horizon:pause

    php artisan horizon:continue

You may gracefully terminate the master Horizon process on your machine using the `horizon:terminate` Artisan command. Any jobs that Horizon is currently processing will be completed and then Horizon will exit:

    php artisan horizon:terminate

### Deploying Horizon

If you are deploying Horizon to a live server, you should configure a process monitor to monitor the `php artisan horizon` command and restart it if it quits unexpectedly. When deploying fresh code to your server, you will need to instruct the master Horizon process to terminate so it can be restarted by your process monitor and receive your code changes.

You may gracefully terminate the master Horizon process on your machine using the `horizon:terminate` Artisan command. Any jobs that Horizon is currently processing will be completed and then Horizon will exit:

    php artisan horizon:terminate

## Notifications

> **Note:** Before using notifications, you should add the `guzzlehttp/guzzle` Composer package to your project. When configuring Horizon to send SMS notifications, you should also review the [prerequisites for the Nexmo notification driver](https://laravel.com/docs/5.4/notifications#sms-notifications).

If you would like to be notified when one of your queues has a long wait time, you may use the `Horizon::routeSlackNotificationsTo` and `Horizon::routeSmsNotificationsTo` methods. You may call these methods from your application's `AppServiceProvider`:

```php
Horizon::routeSlackNotificationsTo('slack-webhook-url');

Horizon::routeSmsNotificationsTo('15556667777');
```

### Configuring Wait Time Thresholds

You may configure how many seconds are considered a "long wait" within your `config/horizon.php` configuration file. The `wait` configuration option within this file allows you to control the long wait threshold for each connection / queue combination:

```php
'waits' => [
    'redis:default' => 60,
],
```
