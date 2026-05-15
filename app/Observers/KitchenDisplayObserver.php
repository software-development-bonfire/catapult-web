<?php

namespace App\Observers;

use App\Entities\KitchenDisplay;
use App\Events\KDSMobile\KDSMobileUpdateEvent;
use Illuminate\Support\Facades\DB;

class KitchenDisplayObserver
{
    public function created(KitchenDisplay $kitchenDisplay)
    {
        $this->broadcastUpdate($kitchenDisplay, 'created');
    }

    public function updated(KitchenDisplay $kitchenDisplay)
    {
        $this->broadcastUpdate($kitchenDisplay, 'updated');
    }

    public function deleted(KitchenDisplay $kitchenDisplay)
    {
        $this->broadcastUpdate($kitchenDisplay, 'deleted');
    }

    private function broadcastUpdate(KitchenDisplay $kitchenDisplay, $action)
    {
        $branchBid = $this->resolveBranchBid($kitchenDisplay->terminal_bid);

        \Log::info('[KDSMobileObserver] Action: ' . $action . ', terminal_bid: ' . $kitchenDisplay->terminal_bid . ', branch_bid: ' . ($branchBid ?? 'null'));

        if ($branchBid) {
            try {
                broadcast(new KDSMobileUpdateEvent(
                    $branchBid,
                    $action,
                    $kitchenDisplay->transaction_id,
                    $kitchenDisplay->terminal_number
                ));
                \Log::info('[KDSMobileObserver] Broadcast sent for branch: ' . $branchBid);
            } catch (\Exception $e) {
                \Log::error('[KDSMobileObserver] Broadcast failed: ' . $e->getMessage());
            }
        } else {
            \Log::warning('[KDSMobileObserver] No branch_bid found, skipping broadcast');
        }
    }

    private function resolveBranchBid($terminalBid)
    {
        if (!$terminalBid) {
            return null;
        }

        $branchBid = DB::table('cdis_terminal')
            ->where('bid', $terminalBid)
            ->value('branch_bid');

        return $branchBid ? (string) $branchBid : null;
    }
}
