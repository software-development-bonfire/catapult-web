<?php

namespace App\Entities;

class CDISKitchenStationProcess extends BaseModel
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

    
    public function syncDetails()
    {
        $referenceBids = array_filter([
            $this->kitchen_station_bid_1,
            $this->kitchen_station_bid_2,
            $this->kitchen_station_bid_3,
            $this->kitchen_station_bid_4
        ]);

        return (object) array(
            'code' => '',
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => json_encode($referenceBids),
            'reference_table' => 'cdis_kitchen_station',
        );
    }
}
