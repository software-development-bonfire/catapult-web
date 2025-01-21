<?php

namespace App\Services;

use App\Entities\CDISKitchenDevicePrinter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KitchenDevicePrinterService
{
    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     * @param  string  $bid
     */
    public function update($data, $bid)
    {
        DB::beginTransaction();
        try {
            $data['updated_by'] = Auth::user()->bid;

            CDISKitchenDevicePrinter::find($bid)->update($data);

            DB::commit();
            return true;
        } catch (\Throwable $th) {
            DB::rollBack();
            return $th->getMessage();
        }
    }
}
