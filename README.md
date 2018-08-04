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
    + jquery
    + boostrap js
    
- publish `config/views/assets`

- register routes
```php
Horizon::routes();
```

## Installation

- add to `composer.json`
```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/ctf0/horizon"
    }
],
"require": {
    "laravel/horizon": "dev-master"
},
```

- add dep
```bash
yarn add axios chart.js bootstrap phpunserialize vue vue-router vue-tippy@v1 laravel-mix-purgecss
```

- compile assets
```js
require('laravel-mix-purgecss')

const mix = require('laravel-mix')
const webpack = require('webpack')

mix.js('resources/assets/vendor/horizon/js/app.js', 'vendor/horizon/js')
    .sass('resources/assets/vendor/horizon/sass/app.scss', 'vendor/horizon/css')
    .copy('resources/assets/vendor/horizon/img', 'vendor/horizon/img')
    .purgeCss({
        enabled: true,
        keyframes: true,
        fontFace: true,
        rejected: true
    })

mix.webpackConfig({
    plugins: [
        new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/)
    ]
})
```

### TODO
- find a way for `lodash/chain` atm we need to import the whole package
