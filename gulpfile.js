const elixir = require('laravel-elixir');

require('laravel-elixir-vue-2');

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application as well as publishing vendor resources.
 |
 */

elixir(mix => {
  mix
    .less('app.less')
    .webpack('app.js')
    .copy('resources/assets/img', 'public/img')
    .copy('public', '../../laravelhorizon/public/vendor/horizon');
    // .copy('public', '../app/public/vendor/horizon');
});
