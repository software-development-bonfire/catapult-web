<?php

namespace App\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
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
        $this->app->bind(\App\Repositories\Contracts\FieldMappingRepository::class, \App\Repositories\Eloquent\FieldMappingRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\RoleRepository::class, \App\Repositories\Eloquent\RoleRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\PermissionRepository::class, \App\Repositories\Eloquent\PermissionRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\UserAccountRepository::class, \App\Repositories\Eloquent\UserAccountRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\SyncIntervalSettingRepository::class, \App\Repositories\Eloquent\SyncIntervalSettingRepositoryEloquent::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadConfiguration();
    }

    /**
     * Load configuration from table.
     *
     * @return void
     */
    private function loadConfiguration()
    {
        try {
            if (Schema::hasTable('configurations')) {
                config([
                    'configuration' => Arr::pluck(
                        \App\Entities\Configuration::all()->toArray(),
                        'value',
                        'attribute'
                    )
                ]);
            }
        } catch (\Exception $e) { }
    }
}
