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
        $this->app->bind(\App\Repositories\Contracts\FileStorageSetupRepository::class, \App\Repositories\Eloquent\FileStorageSetupRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\FieldMappingRepository::class, \App\Repositories\Eloquent\FieldMappingRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\RoleRepository::class, \App\Repositories\Eloquent\RoleRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\PermissionRepository::class, \App\Repositories\Eloquent\PermissionRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\UserAccountRepository::class, \App\Repositories\Eloquent\UserAccountRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\SyncIntervalSettingRepository::class, \App\Repositories\Eloquent\SyncIntervalSettingRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\FieldMappingPresetRepository::class, \App\Repositories\Eloquent\FieldMappingPresetRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\DataMappingRepository::class, \App\Repositories\Eloquent\DataMappingRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\ErrorLogRepository::class, \App\Repositories\Eloquent\ErrorLogRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\ErrorLogDetailRepository::class, \App\Repositories\Eloquent\ErrorLogDetailRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\CDIS\BranchRepository::class, \App\Repositories\Eloquent\CDIS\BranchRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\CDIS\TerminalTransactionRepository::class, \App\Repositories\Eloquent\CDIS\TerminalTransactionRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\CDIS\CashBreakdownRepository::class, \App\Repositories\Eloquent\CDIS\CashBreakdownRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\CDIS\POSAuditTrailRepository::class, \App\Repositories\Eloquent\CDIS\POSAuditTrailRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\DeviceSettingsRepository::class, \App\Repositories\Eloquent\DeviceSettingsRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\ItemAvailabilityRepository::class, \App\Repositories\Eloquent\ItemAvailabilityRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\POS\TerminalTransactionRepository::class, \App\Repositories\Eloquent\POS\TerminalTransactionRepositoryEloquent::class);
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
