<?php

namespace App\Services;

use App\Entities\ApiSetup;
use App\Traits\DatabaseTransaction;
use App\Traits\QueryHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

class ApiSetupService
{
    use DatabaseTransaction;

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
    public function update($data, $bid)
    {
        return $this->transaction(function() use($bid, $data) {
            $data['updated_by'] = Auth::user()->bid;
            $update = ApiSetup::find($bid)->update($data);

            return $update;
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $bid
     * 
     */
    public function destroy($bid)
    {
        ApiSetup::find($bid)->delete();
    }
}


