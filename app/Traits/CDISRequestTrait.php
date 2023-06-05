<?php

namespace App\Traits;

use App\Observers\BidObserver;
use Illuminate\Support\Carbon;

/**
 * Trait CDISRequestTrait
 * @package App\Traits
 */
trait CDISRequestTrait
{
    private function getSenderDetails()
    {
        return [
            'client_id' => config('configuration.client_id'),
            'product_key' => config('configuration.product_key'),
            'branch_code' => config('configuration.branch_code'),
            'system_datetime' => Carbon::now()->format('Y-m-d h:i:s'),
        ];
    }
}
