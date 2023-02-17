<?php

namespace App\Traits;

use App\Observers\BidObserver;

/**
 * Trait BidObserverTrait
 * @package App\Traits
 */
trait BidObserverTrait
{
    public static function bootBidObserverTrait()
    {
        static::observe(new BidObserver());
    }
}
