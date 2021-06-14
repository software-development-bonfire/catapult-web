<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(\App\Repositories\Contracts\ApiSetupRepository::class, \App\Repositories\Eloquent\ApiSetupRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\CatapultDbSetupRepository::class, \App\Repositories\Eloquent\CatapultDbSetupRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\RemoteSetupRepository::class, \App\Repositories\Eloquent\RemoteSetupRepositoryEloquent::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
