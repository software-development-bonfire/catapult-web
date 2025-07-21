<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;


class CDISOrderTakingItemSetupAddonDetail extends Base 
{
    use SoftDeletes;

    protected $table = 'cdis_order_taking_item_setup_addon_detail';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'head_bid',
        'product_uom_packaging_bid',
        'display_name',
        'is_available',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
    ];

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => $this->table,
            'head_bid' => null,
            'level' => 4,
            'reference_bid' => null,
            'reference_table' => null,
        );
    }

}
