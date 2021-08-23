<?php

namespace App\Services;

use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Traits\DatabaseTransaction;

class KitchenDisplayService
{
    use DatabaseTransaction;

    /**
     * Move menu to other station.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function moveMenu($data)
    {
        return $this->transaction(function () use($data) {
            $kitchenDisplayDetail = KitchenDisplayDetail::find($data['kitchen_display_detail_bid']);

            $kitchenDisplayDetail->update([
                'kitchen_station_bid' => $data['move_station_bid']
            ]);

            return true;
        });
    }

    /**
     * Remove order in kitchen display.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function removeOrder($data)
    {
        return $this->transaction(function () use($data) {
            $kitchenDisplay = KitchenDisplay::find($data['kitchen_display_bid']);

            $kitchenDisplay->details()->each(function($detail) {
                $detail->delete();
            });

            $kitchenDisplay->delete();

            return true;
        });
    }

    /**
     * Remove menu in kitchen display.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function removeMenu($data)
    {
        return $this->transaction(function () use($data) {
            $kitchenDisplayDetail = KitchenDisplayDetail::find($data['kitchen_display_detail_bid']);

            $kitchenDisplayDetail->delete();

            return true;
        });
    }
}


