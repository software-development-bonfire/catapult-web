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
        $this->app->bind(\App\Repositories\Contracts\SyncEntryRepository::class, \App\Repositories\Eloquent\SyncEntryRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\TerminalFileSetupRepository::class, \App\Repositories\Eloquent\TerminalFileSetupRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\CostAndPriceChangeRepository::class, \App\Repositories\Eloquent\CostAndPriceChangeRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\CDIS\TerminalTransactionRepository::class, \App\Repositories\Eloquent\CDIS\TerminalTransactionRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\CDIS\ZReadRepository::class, \App\Repositories\Eloquent\CDIS\ZReadRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\CDIS\POSAuditTrailRepository::class, \App\Repositories\Eloquent\CDIS\POSAuditTrailRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\CDIS\CashDrawerRepository::class, \App\Repositories\Eloquent\CDIS\CashDrawerRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\CDIS\CashBreakdownRepository::class, \App\Repositories\Eloquent\CDIS\CashBreakdownRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\DeviceSettingsRepository::class, \App\Repositories\Eloquent\DeviceSettingsRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\CDISProductCategoryRepository::class, \App\Repositories\Eloquent\CDISProductCategoryRepositoryEloquent::class);
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
