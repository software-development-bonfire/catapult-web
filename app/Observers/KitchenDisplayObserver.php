<?php

namespace App\Observers;

use App\Entities\KitchenDisplay;
use App\Events\KDSMobile\KDSMobileUpdateEvent;
use Illuminate\Support\Facades\DB;

class KitchenDisplayObserver
{
    public function created(KitchenDisplay $kitchenDisplay): void
    {
        $this->broadcastUpdate($kitchenDisplay, 'created');
    }

    public function updated(KitchenDisplay $kitchenDisplay): void
    {
        $this->broadcastUpdate($kitchenDisplay, 'updated');
    }

    public function deleted(KitchenDisplay $kitchenDisplay): void
    {
        $this->broadcastUpdate($kitchenDisplay, 'deleted');
    }

    private function broadcastUpdate(KitchenDisplay $kitchenDisplay, string $action): void
    {
        $branchBid = $this->resolveBranchBid($kitchenDisplay->terminal_bid);

        if ($branchBid) {
            broadcast(new KDSMobileUpdateEvent(
                $branchBid,
                $action,
                $kitchenDisplay->transaction_id,
                $kitchenDisplay->terminal_number
            ));
        }
    }

    private function resolveBranchBid(?string $terminalBid): ?string
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
