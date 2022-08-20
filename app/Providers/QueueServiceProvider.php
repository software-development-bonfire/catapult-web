<?php

namespace App\Providers;

use App\Traits\GenericHelper;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    use GenericHelper;
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
        Queue::before(function (JobProcessing $event) {            
            //Log::info('Connection: '. $event->connectionName);
            //Log::info(json_encode($event));
        });
 
        Queue::after(function (JobProcessed $event) {
            //Log::info('Connection: '. $event->connectionName);
            //Log::info(json_encode($event));
        });
    }
}
