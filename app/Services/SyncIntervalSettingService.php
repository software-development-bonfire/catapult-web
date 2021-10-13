<?php

namespace App\Services;

use App\Entities\SyncIntervalSetting;
use App\Enums\Status;
use Illuminate\Support\Facades\Auth;

class SyncIntervalSettingService
{
    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     * @return \Illuminate\Http\Response
     */
    public function store($data)
    {
        if ($data['status'] == Status::ACTIVE) {
            SyncIntervalSetting::where('syncing_type', $data['syncing_type'])
            ->update(['status' => Status::INACTIVE]);
        }

        $data['created_by'] = Auth::user()->bid;

        SyncIntervalSetting::create($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function update($data, $bid)
    {
        if ($data['status'] == Status::ACTIVE) {
            SyncIntervalSetting::where('syncing_type', $data['syncing_type'])
            ->update(['status' => Status::INACTIVE]);
        }

        $data['updated_by'] = Auth::user()->bid;
        
        SyncIntervalSetting::find($bid)->update($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $bid
     *
     */
    public function destroy($bid)
    {
        SyncIntervalSetting::find($bid)->delete();
    }
}


