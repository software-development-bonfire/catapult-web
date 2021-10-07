<?php

namespace App\Entities;


use Illuminate\Database\Eloquent\Model;

class CDISKitchenStationProcess extends Model
{
    protected $table = 'cdis_kitchen_station_process';

    protected $fillable = [
        'bid',
        'code',
        'description',
        'kitchen_station_bid_1',
        'kitchen_station_bid_2',
        'kitchen_station_bid_3',
        'kitchen_station_bid_4',
        'status'
    ];

    protected $casts = [
        'bid' => 'string',
        'kitchen_station_bid_1' => 'string',
        'kitchen_station_bid_2' => 'string',
        'kitchen_station_bid_3' => 'string',
        'kitchen_station_bid_4' => 'string',
    ];
}
