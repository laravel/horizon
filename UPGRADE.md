# Upgrade Guide

## Upgrading To 3.0 From 2.0

### Minimum Laravel version

Laravel 5.7 is now the minimum supported version of the framework and you should upgrade to continue using Horizon.

## Upgrading To 3.0 From 1.0

Make sure you re-publish the assets:

```
php artisan vendor:publish --tag=horizon-assets --force
```
