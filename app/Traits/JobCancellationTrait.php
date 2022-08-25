<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait JobCancellationTrait
{    
    public function hasBeenCancelledConversion() 
    {
        $hasBeenCancelled = false;
        if (Cache::has('conversion_cancelled')) {
            $value = Cache::get('conversion_cancelled');
            $hasBeenCancelled = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? true: false;
        }
        return $hasBeenCancelled;
    }

    public function clearCancelledConversion()
    {
        Cache::forget('conversion_cancelled');
    }

    public function setCancelledConversion()
    {
        Cache::put('conversion_cancelled', 'true', now()->addHour(1));
    }
    
    public function hasBeenCancelledSyncing() 
    {
        $hasBeenCancelled = false;
        if (Cache::has('syncing_cancelled')) {
            $value = Cache::get('syncing_cancelled');
            $hasBeenCancelled = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? true: false;
        }
        return $hasBeenCancelled;
    }

    public function clearCancelledSyncing()
    {
        Cache::forget('syncing_cancelled');
    }

    public function setCancelledSyncing()
    {
        Cache::put('syncing_cancelled', 'true', now()->addHour(1));
    }
	
    public function isSyncing() 
    {
        $isSyncing = false;
        if (Cache::has('syncing')) {
            $value = Cache::get('syncing');
            $isSyncing = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? true: false;
        }
        return $isSyncing;
    }
	
    public function clearSyncing()
    {
        Cache::forget('syncing');
    }

    public function setSyncing()
    {
        Cache::put('syncing', 'true', now()->addHour(1));
    }
	
    public function isConverting() 
    {
        $isConverting = false;
        if (Cache::has('converting')) {
            $value = Cache::get('converting');
            $isConverting = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? true: false;
        }
        return $isConverting;
    }
	
    public function clearConverting()
    {
        Cache::forget('converting');
    }

    public function setConverting()
    {
        Cache::put('converting', 'true', now()->addHour(1));
    }


}
