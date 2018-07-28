<p align="center"><img src="https://laravel.com/assets/img/components/logo-horizon.svg"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/horizon"><img src="https://travis-ci.org/laravel/horizon.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/horizon"><img src="https://poser.pugx.org/laravel/horizon/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/horizon"><img src="https://poser.pugx.org/laravel/horizon/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/horizon"><img src="https://poser.pugx.org/laravel/horizon/license.svg" alt="License"></a>
</p>

## Official Documentation

Documentation for Horizon can be found on the [Laravel website](http://laravel.com/docs/horizon).

---

## Diff

- removed
    + middleware
    + `Horizon::auth()`
    + public dist
- add to publish `config/views/assets`
- register routes `Horizon::routes();`
- add to `composer.json`
```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/ctf0/horizon"
    }
],
"require": {
    "laravel/horizon": "^1.3"
},
```
