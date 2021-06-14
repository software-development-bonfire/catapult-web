<?php

namespace App\Services;

use App\Entities\RemoteSetup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

class RemoteSetupService
{
    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     * @return \Illuminate\Http\Response
     */
    public function store($data)
    {
        try {
            $data['created_by'] = Auth::user()->bid;
            $remote_setup = RemoteSetup::create($data);
    
            if ($remote_setup) {
                return [
                    'data' => $remote_setup,
                    'message' => Lang::get('success.remote_setup_created')
                ];
            }
        } catch (\Throwable $th) {
            return ['message' => Lang::get('error.remote_setup_failed_create')];
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($data, $bid)
    {
        try {
            if ($data['password'] === null) {
                unset($data['password']);
            }
            $data['updated_by'] = Auth::user()->bid;

            $update = RemoteSetup::find( $bid)->update($data);

            if ($update) {
                return ['message' => Lang::get('success.remote_setup_updated')];
            }
        } catch (\Throwable $th) {
            return ['message' => Lang::get('error.remote_setup_failed_update')];

        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $bid
     *
     */
    public function destroy($bid)
    {
        RemoteSetup::find($bid)->delete();
    }
}


