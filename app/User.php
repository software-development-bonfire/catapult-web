<?php

namespace App;

use App\Entities\UserPermission;
use App\Enums\Permissions;
use App\Enums\UserType;
use App\Traits\BidObserverTrait;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Arr;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable,
        SoftDeletes,
        BidObserverTrait,
        HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'bid';
    
    protected $fillable = [
        'name',
        'username',
        'password',
        'type',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'bid' => 'string'
    ];

    public function isSuperadmin()
    {
        return $this->type === UserType::SUPERADMIN;
    }

    public function getPermissions()
    {
        $permissions = array();

        if ($this->isSuperadmin()) {
            foreach ($this->getPermissionList() as $permission) {
                array_push($permissions, $permission);
            }
        } else {
            $permissions = UserPermission::where([
                ['user_bid', '=', $this->bid]
            ])
            ->pluck('code')
            ->toArray();
        }
        return $permissions;
    }

    public function getPermissionList()
    {
        return Arr::dot(Permissions::LIST);
    }

    public function permissions()
    {
        return $this->hasMany(UserPermission::class, 'user_bid', 'bid');
    }
}
