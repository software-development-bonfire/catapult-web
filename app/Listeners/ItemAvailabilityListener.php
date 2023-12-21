<?php

namespace App\Listeners;

use App\Events\ItemAvailability;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ItemAvailabilityListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  ItemAvailability  $event
     * @return void
     */
    public function handle(Products $event)
    {
       Log::info($event->message);
       Log::info('hey');
    }
}
