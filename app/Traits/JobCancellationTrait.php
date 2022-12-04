<?php

namespace App\Traits;

use App\Entities\SyncDetail;
use App\Enums\CatapultSyncStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

trait JobCancellationTrait
{
    public function hasBeenCancelledConversion()
    {
        $hasBeenCancelled = false;
        if (Cache::has('conversion_cancelled')) {
            $value = Cache::get('conversion_cancelled');
            $hasBeenCancelled = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? true : false;
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
            $hasBeenCancelled = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? true : false;
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
            $isSyncing = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? true : false;
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
        $this->setSyncStatus(CatapultSyncStatus::Syncing);
    }

    public function isConverting()
    {
        $isConverting = false;
        if (Cache::has('converting')) {
            $value = Cache::get('converting');
            $isConverting = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? true : false;
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
        $this->setSyncStatus(CatapultSyncStatus::Converting);
    }

    public function setSyncStatus($status, $description = null)
    {
        $branchCode = config('configuration.branch_code');
        if (! empty($branchCode)) {
            $data = [
                'code' => $branchCode,
                'state' => $status,
                'description' => $description,
                'sync_entry' => 0
            ];
            $syncDetail = SyncDetail::where('code', '=', $branchCode)->first();
            if (empty($syncDetail)) {
                $syncDetail = SyncDetail::create($data);
            } else {
                $syncDetail->update($data);
            }
        }
    }

    public function getSyncStatus()
    {
        $status = "";
        $branchCode = config('configuration.branch_code');
        if (! empty($branchCode)) {
            $syncDetail = SyncDetail::where('code', '=', $branchCode)->first();
            if (! empty($syncDetail)) {
                $status = $syncDetail->state;
            }
        }
        return $status;
    }


    public function setResolveStatus($status, $count = 1, $description = null)
    {
        $data = [
            'code' => 0,
            'state' =>  $status,
            'description' => $description,
            'sync_entry' => $count
        ];
        $syncDetail = SyncDetail::where('code', '=', 0)->first();
        if (empty($syncDetail)) {
            $syncDetail = SyncDetail::create($data);
        } else {
            $syncDetail->update($data);
        }
    }

    public function isResolvedBeenExecuted()
    {
        $resolveExecuted =  false;
        $syncDetail = SyncDetail::where('code', '=', 0)->first();
        if (! empty($syncDetail)) {
            $resolveExecuted = ($syncDetail->state === true && $syncDetail->sync_entry > 1);
        }
        return $resolveExecuted;
    }

    public function checkResolvedStatus()
    {
        $syncDetail = SyncDetail::where('code', '=', 0)->where('online_at', '<=', Carbon::now()->subHours(2))->first();
        if (! empty($syncDetail)) {
            $syncDetail->update(['state' => false, 'sync_entry' => 0]);
        }
        $syncDetail = SyncDetail::where('code', '=', 0)->first();
        return $syncDetail->sync_entry;
    }
}
