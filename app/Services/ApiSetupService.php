<?php

namespace App\Services;

use App\Entities\ApiSetup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

class ApiSetupService
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
            $api_setup = ApiSetup::create($data);
            
            if ($api_setup) {
                return [
                    'data' => $api_setup,
                    'message' => Lang::get('success.api_setup_created')
                ];
            }
        } catch (\Throwable $th) {
            return response()->json(['message' => 'error.api_setup_failed_create'], 500);
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
            $update = ApiSetup::findOrFail($id)->update($data);

            if ($update) {
                return ['message' => Lang::get('success.api_setup_updated')];
            }
        } catch (\Throwable $th) {
            return ['message' => Lang::get('error.api_setup_failed_update')];

        }
    }

    public function destroy($id)
    {
        ApiSetup::findOrFail($id)->delete();
        return ['message' => Lang::get('success.api_setup_deleted')];
    }
}


