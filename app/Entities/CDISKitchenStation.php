<?php

namespace App\Entities;

class CDISKitchenStation extends BaseModel
{
    protected $table = 'cdis_kitchen_station';

    protected $fillable = [
        'bid',
        'code',
        'name',
        'queueing_group_type',
        'screen_prioritization',
        'status'
    ];

    public function kitchenUserStation()
    {
        return $this->hasMany(CDISKitchenUserStation::class, 'kitchen_station_bid', 'bid');
    }
}
