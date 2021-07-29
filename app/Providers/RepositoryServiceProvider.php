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
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(\App\Repositories\EntryCounterRepository::class, \App\Repositories\EntryCounterRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\FolderCounterRepository::class, \App\Repositories\FolderCounterRepositoryEloquent::class);
        //:end-bindings:
    }
}
