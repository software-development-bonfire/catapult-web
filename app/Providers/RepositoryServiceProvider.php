<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(\App\Repositories\Contracts\KitchenStationRepository::class, \App\Repositories\Eloquent\KitchenStationRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\KitchenStationProcessRepository::class, \App\Repositories\Eloquent\KitchenStationProcessRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\KitchenDisplayRepository::class, \App\Repositories\Eloquent\KitchenDisplayRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\CDISKitchenUserRepository::class, \App\Repositories\Eloquent\CDISKitchenUserRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\CDISProductVariantRepository::class, \App\Repositories\Eloquent\CDISProductVariantRepositoryEloquent::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //:end-bindings:
    }
}
