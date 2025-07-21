<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;


class CDISOrderTakingItemSetupBarcode extends Base
{
    use SoftDeletes;

    protected $table = 'cdis_order_taking_item_setup_barcode';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'head_bid',
        'product_uom_packaging_bid',
        'status',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'product_uom_packaging_bid' => 'string',
    ];

    public function setupDetail()
    {
        return $this->belongsTo(CDISOrderTakingItemSetupDetail::class, 'head_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => $this->table,
            'head_bid' => null,
            'level' => 3,
            'reference_bid' => null,
            'reference_table' => null,
        );
    }
}
