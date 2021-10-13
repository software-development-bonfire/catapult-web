<?php

namespace App\Services;

use App\Entities\FileStorageSetup;
use App\Traits\DatabaseTransaction;
use Illuminate\Support\Facades\Auth;

class FileStorageSetupService
{
    use DatabaseTransaction;

    /**
     * Update the specified resource in storage.
     *
     * @param array  $data
     * @return FileStorageSetup
     */
    public function store($data)
    {
        return $this->transaction(function() use($data) {
            $data['created_by'] = Auth::user()->bid;
            $FileStorageSetup = FileStorageSetup::create($data);

            return $FileStorageSetup;
        });
    }

    /**
     * Update the specified resource in storage.
     *
     * @param array  $data
     * @param string  $bid
     * @return FileStorageSetup
     */
    public function update($data, $bid)
    {
        return $this->transaction(function() use($data, $bid) {
            if ($data['password'] === null) {
                unset($data['password']);
            }
            $data['updated_by'] = Auth::user()->bid;

            $FileStorageSetup = FileStorageSetup::find($bid)->update($data);

            return $FileStorageSetup;
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $bid
     * @return FileStorageSetup
     */
    public function destroy($bid)
    {
        return $this->transaction(function() use($bid) {
            return FileStorageSetup::find($bid)->delete();
        });
    }
}


