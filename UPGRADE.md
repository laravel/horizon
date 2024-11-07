# Upgrade Guide 

## Upgrading To 5.0 From 4.x

### Minimum PHP Version

PHP 7.3 is now the minimum required version.

### Minimum Laravel Version

Laravel 8.0 is now the minimum required version.

### Chronos Replaced By Carbon

PR: https://github.com/laravel/horizon/pull/826

The internal usage of Chronos has been replaced by Carbon to be consistent with the rest of the Laravel ecosystem.

### `timeoutAt` & `delay` Flags Deprecated

PR: https://github.com/laravel/horizon/commit/6d00eb9b80a599d3ac403108b7a8d65629af2c59

`timeoutAt` has been deprecated in favor of `retryUntil`, while `delay` has been deprecated in favor of `backoff`. See the related Laravel PR for more information: https://github.com/laravel/framework/pull/32728


## Upgrading To 4.0 From 3.x

### Minimum Laravel Version

PR: https://github.com/laravel/horizon/pull/710

Laravel 7.0 is now the minimum required version of the framework.

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

Laravel 5.7 is now the minimum required version of the framework and you should upgrade to continue using Horizon.
