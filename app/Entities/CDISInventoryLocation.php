<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISInventoryLocation extends BaseModel
{
    protected $table = 'cdis_inventory_location';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'name',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string'
    ];
}
