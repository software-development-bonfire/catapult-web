<?php

namespace App\Entities;

use Illuminate\Support\Facades\Route;

class CDISKitchenUserStation extends BaseModel
{
    protected $table = 'cdis_kitchen_user_station';

    protected $primaryKey = 'bid';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'bid' => 'string',
        'kitchen_station_bid' => 'string',
        'kitchen_user_bid' => 'string'
    ];

    protected $fillable = [
        'bid',
        'kitchen_station_bid',
        'kitchen_user_bid'
    ];

    public function kitchenStation()
    {
        return $this->belongsTo(CDISKitchenStation::class, 'kitchen_station_bid', 'bid');
    }

    public function kitchenUser()
    {
        return $this->belongsTo(CDISKitchenUser::class, 'kitchen_user_bid', 'bid');
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
            $syncDetails->group = $this->kitchenUser->getTable();
            $syncDetails->head_bid = $this->kitchen_user_bid;
            $syncDetails->reference_bid = $this->kitchen_user_bid;
            $syncDetails->reference_table =  $this->kitchenUser->getTable();
            $syncDetails->level = 2;
        }

        return $syncDetails;
    }
}
