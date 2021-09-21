<?php

namespace App\Entities;


class CDISKitchenStationProcess extends Base
{
    protected $table = 'cdis_kitchen_station_process';

    protected $fillable = [
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
