<?php

namespace Laravel\Horizon;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class HorizonApplicationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->authorization();
    }

    /**
     * Configure the Horizon authorization services.
     *
     * @return void
     */
    protected function authorization()
    {
        $this->gate();

        Horizon::auth(function ($request) {
            return Gate::check('viewHorizon', [$request->user()]) || app()->environment('local');
        });
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewHorizon', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
