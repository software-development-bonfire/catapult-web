<?php

namespace App\Entities;

class CDISInventoryLocationTag extends BaseModel
{
    protected $table = 'cdis_inventory_location_tag';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'head_bid',
        'type',
        'inventory_location_bid',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'inventory_location_bid' => 'string',
    ];
}
