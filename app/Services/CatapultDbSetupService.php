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
            $data['status'] = $data['status'] == 'Active' ? 1: 0;
            $data['created_by'] = Auth::user()->bid;
            $catapult_db_setup = CatapultDbSetup::create($data);
    
            if ($catapult_db_setup) {
                return [
                    'data' => $catapult_db_setup,
                    'message' => Lang::get('success.catapult_db_setup_created')
                ];
            }
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
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

            $update = CatapultDbSetup::findOrFail($id)->update($data);

            if ($update) {
                return ['message' => Lang::get('success.catapult_db_setup_updated')];
            }
        } catch (\Throwable $th) {
            return response()->json(['message' => Lang::get('error.catapult_db_setup_failed_update')], 500);

        }
    }

    public function destroy($id)
    {
        CatapultDbSetup::findOrFail($id)->delete();
        return ['message' => Lang::get('success.catapult_db_setup_deleted')];
    }
}


