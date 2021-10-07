<?php

namespace App\Services;

use App\Entities\RemoteSetup;
use App\Traits\DatabaseTransaction;
use Illuminate\Support\Facades\Auth;

class RemoteSetupService
{
    use DatabaseTransaction;

    /**
     * Update the specified resource in storage.
     *
     * @param array  $data
     * @return RemoteSetup
     */
    public function store($data)
    {
        return $this->transaction(function() use($data) {
            $data['created_by'] = Auth::user()->bid;
            $remoteSetup = RemoteSetup::create($data);

            return $remoteSetup;
        });
    }

    /**
     * Update the specified resource in storage.
     *
     * @param array  $data
     * @param string  $bid
     * @return RemoteSetup
     */
    public function update($data, $bid)
    {
        return $this->transaction(function() use($data, $bid) {
            if ($data['password'] === null) {
                unset($data['password']);
            }
            $data['updated_by'] = Auth::user()->bid;

            $remoteSetup = RemoteSetup::find($bid)->update($data);

            return $remoteSetup;
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $bid
     * @return RemoteSetup
     */
    public function destroy($bid)
    {
        return $this->transaction(function() use($bid) {
            return RemoteSetup::find($bid)->delete();
        });
    }
}


