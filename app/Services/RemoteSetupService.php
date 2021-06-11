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
            $data['status'] = $data['status'] == 'Active' ? 1: 0;
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
    public function update($data, $id)
    {
        try {
            $data['status'] = $data['status'] == 'Active' ? 1: 0;
            if ($data['password'] === null) {
                unset($data['password']);
            }

            $update = RemoteSetup::findOrFail($id)->update($data);

            if ($update) {
                return ['message' => Lang::get('success.remote_setup_updated')];
            }
        } catch (\Throwable $th) {
            return ['message' => Lang::get('error.remote_setup_failed_update')];

        }
    }

    public function destroy($id)
    {
        RemoteSetup::findOrFail($id)->delete();
        return ['message' => Lang::get('success.remote_setup_deleted')];
    }
}


