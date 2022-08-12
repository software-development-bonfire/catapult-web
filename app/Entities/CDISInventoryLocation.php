<?php

namespace App\Entities;

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

    public function inventoryLocationTag()
    {
        return $this->hasMany(CDISInventoryLocationTag::class, 'head_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => null,
            'head_bid' => null,
            'level' => 1,
        );
    }
}
