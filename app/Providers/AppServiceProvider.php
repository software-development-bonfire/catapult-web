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
        $this->app->bind(\App\Repositories\Contracts\FieldMappingRepository::class, \App\Repositories\Eloquent\FieldMappingRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\RoleRepository::class, \App\Repositories\Eloquent\RoleRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\PermissionRepository::class, \App\Repositories\Eloquent\PermissionRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\UserAccountRepository::class, \App\Repositories\Eloquent\UserAccountRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\SyncIntervalSettingRepository::class, \App\Repositories\Eloquent\SyncIntervalSettingRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\FieldMappingListRepository::class, \App\Repositories\Eloquent\FieldMappingListRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\DataMappingRepository::class, \App\Repositories\Eloquent\DataMappingRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\ErrorLogRepository::class, \App\Repositories\Eloquent\ErrorLogRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\ErrorLogDetailRepository::class, \App\Repositories\Eloquent\ErrorLogDetailRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\EntryCounterRepository::class, \App\Repositories\Eloquent\EntryCounterRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\FolderCounterRepository::class, \App\Repositories\Eloquent\FolderCounterRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\SystemLogRepository::class, \App\Repositories\Eloquent\SystemLogRepositoryEloquent::class);
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
