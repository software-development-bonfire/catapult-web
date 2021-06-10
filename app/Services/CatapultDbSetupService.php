<?php

namespace App\Services;

use App\Entities\CatapultDbSetup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

class CatapultDbSetupService
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
            $catapult_db_setup = CatapultDbSetup::create([
                'name' => $data['name'],
                'host' => $data['host'],
                'port' => $data['port'],
                'db_name' => $data['db_name'],
                'username' => $data['username'],
                'password' => $data['password'],
                'status' => $data['status'] == 'Active' ? 1: 0,
                'created_by' => Auth::user()->bid,
            ]);
    
            if ($catapult_db_setup) {
                return ['message' => Lang::get('success.catapult_db_setup_created')];
            }
        } catch (\Throwable $th) {
            return ['message' => Lang::get('error.catapult_db_setup_failed_create')];
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

            $update = CatapultDbSetup::findOrFail($id)->update($data);

            if ($update) {
                return ['message' => Lang::get('success.catapult_db_setup_updated')];
            }
        } catch (\Throwable $th) {
            return ['message' => Lang::get('error.catapult_db_setup_failed_update')];

        }
    }

    public function destroy($id)
    {
        CatapultDbSetup::findOrFail($id)->delete();
        return ['message' => Lang::get('success.catapult_db_setup_deleted')];
    }
}


