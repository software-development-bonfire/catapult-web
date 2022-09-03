<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\HasApiTokens;

class CDISKitchenUser extends BaseModel
{
    use HasApiTokens, SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'cdis_kitchen_user';

    /**
     * @var string
     */
    protected $primaryKey = 'bid';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'user_code',
        'full_name',
        'username',
        'password',
        'status',
        'type',
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
     * @var array
     */
    protected $casts = [
        'bid' => 'string'
    ];

    public function kitchenUserBranch()
    {
        return $this->hasMany(CDISKitchenUserBranch::class, 'kitchen_user_bid', 'bid');
    }

    public function accessibleBranches()
    {
        return $this->belongsToMany(
            CDISBranch::class,
            CDISKitchenUserBranch::class,
            'kitchen_user_bid',
            'branch_bid'
        );
    }

    public function allowedStations()
    {
        return $this->belongsToMany(
            CDISKitchenStation::class,
            CDISKitchenUserStation::class,
            'kitchen_user_bid',
            'kitchen_station_bid'
        );
    }

    public function syncDetails()
    {
        $syncDetails = (object) array(
            'code' => '',
            'group' => '',
            'head_bid' => '',
            'level' => 0,
            'reference_bid' => null,
            'reference_table' => null,
        );

        $routeName = Route::currentRouteName();

        if (
            $routeName == 'store_kitchen_user_account'
            || $routeName == 'update_kitchen_user_account'
        ) {
            $syncDetails->code = null;
            $syncDetails->group = $this->getTable() ?? 'cdis_kitchen_user';
            $syncDetails->head_bid = $this->bid;
            $syncDetails->level = 1;
        }

        return $syncDetails;
    }

}
