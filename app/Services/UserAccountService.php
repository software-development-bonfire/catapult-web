<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class UserAccountService
{
    /**
     * Store the specified resource in storage.
     *
     * @param Array  $data
     * @return \Illuminate\Http\Response
     */
    public function store($data)
    {
        DB::transaction(function () use ($data) {
            $data['created_by'] = Auth::user()->bid;
        
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'status' => $data['status'],
                'password' => bcrypt($data['password']),
                'created_by' => $data['created_by'],
            ]);
            $user = User::where('username', $data['username'])->first();

            $this->attachPermissions($user, $data['permission']);
        });
    }

    /**
     * Create the specified resource in storage.
     *
     * @param Object $user
     * @param Array $permissions
     */
    public function attachPermissions($user, $permissions)
    {
        $user->permissions()->delete();

        foreach ($permissions as $permission) {
            $user->permissions()->create(array(
                'code' => $permission['code'],
                'updated_by' => Auth::user()->bid,
            ));
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
        $data['updated_by'] = Auth::user()->bid;
        
        $user = User::find($bid);

        $user->update($data);

        $this->attachPermissions($user, $data['permission']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $bid
     * 
     */
    public function destroy($bid)
    {
        User::find($bid)->delete();
    }
}


