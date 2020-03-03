# Upgrade Guide

With every upgrade, make sure to publish Horizon's assets:

    php artisan horizon:publish

## Upgrading To 4.0 From 3.x

### Minimum Laravel Version

PR: https://github.com/laravel/horizon/pull/710

Laravel 7.0 is now the minimum supported version of the framework.

### Predis No Longer Required

PR: https://github.com/laravel/horizon/pull/531

Because Predis is no longer maintained, it's no longer a required dependency. If you want to continue to use Predis, you should explicitly require it in your `composer.json` file.

### Default Predis Prefix

PR: https://github.com/laravel/horizon/pull/643

The default Predis prefix now starts with the app name. If you would like to continue using the old prefix, you should add `HORIZON_PREFIX="horizon:"` to your `.env` file.

### Horizon Assets Command

PR: https://github.com/laravel/horizon/pull/696

The `horizon:assets` command has been renamed to `horizon:publish` to be similar to other first party packages.

## Upgrading To 3.0 From 2.x

### Minimum Laravel version

Laravel 5.7 is now the minimum supported version of the framework and you should upgrade to continue using Horizon.
