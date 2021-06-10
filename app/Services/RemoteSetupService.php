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
            $remote_setup = RemoteSetup::create([
                'name' => $data['name'],
                'path' => $data['path'],
                'server' => $data['server'],
                'host' => $data['host'],
                'port' => $data['port'],
                'username' => $data['username'],
                'password' => $data['password'],
                'status' => $data['status'] == 'Active' ? 1: 0,
                'created_by' => Auth::user()->bid,
            ]);
    
            if ($remote_setup) {
                return ['message' => Lang::get('success.remote_setup_created')];
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


