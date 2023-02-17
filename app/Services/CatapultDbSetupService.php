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
            $data['created_by'] = Auth::user()->bid;
            $catapult_db_setup = CatapultDbSetup::create($data);
    
            if ($catapult_db_setup) {
                return [
                    'data' => $catapult_db_setup,
                    'message' => Lang::get('success.catapult_db_setup_created')
                ];
            }
        } catch (\Throwable $th) {
            return response()->json(['message' => Lang::get('success.catapult_db_setup_failed_create')], 500);
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

            $update = CatapultDbSetup::find($bid)->update($data);

            if ($update) {
                return ['message' => Lang::get('success.catapult_db_setup_updated')];
            }
        } catch (\Throwable $th) {
            return response()->json(['message' => Lang::get('error.catapult_db_setup_failed_update')], 500);

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
        CatapultDbSetup::find($bid)->delete();
    }
}


