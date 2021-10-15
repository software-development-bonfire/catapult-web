<?php

namespace App\Entities;


class CDISKitchenItemSetup extends BaseModel
{
    protected $table = 'cdis_kitchen_item_setup';

    protected $fillable = [
        'code',
        'branch_bid',
        'device_type_bid',
        'status'
    ];

    protected $casts = [
        'bid' => 'string',
        'branch_bid' => 'string',
    ];
}
