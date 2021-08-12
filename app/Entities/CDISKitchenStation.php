<?php

namespace App\Entities;


class CDISKitchenStation extends Base
{
    protected $table = 'cdis_kitchen_station';

    protected $fillable = [
        'code',
        'name',
        'queueing_group_type',
        'screen_prioritization',
        'status'
    ];
}
