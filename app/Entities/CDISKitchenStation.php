<?php

namespace App\Entities;


use Illuminate\Database\Eloquent\Model;

class CDISKitchenStation extends Model
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
}
