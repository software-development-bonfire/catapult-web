<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class CDISKitchenUserStation extends Model
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
}
