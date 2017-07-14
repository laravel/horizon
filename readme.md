<p align="center"><img src="https://laravel.com/assets/img/components/logo-horizon.svg"></p>

## Introduction

Horizon provides a beautiful dashboard and code-driven configuration for your Laravel powered Redis queues. Horizon is developed by the core developers of the Laravel framework and provides a robust queue monitoring solution for Laravel's Redis queue. Horizon allows you to easily monitor key metrics of your queue system such as job throughput, runtime, and job failures.

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

    Horizon::auth(function ($request) {
        // return true / false;
    });
