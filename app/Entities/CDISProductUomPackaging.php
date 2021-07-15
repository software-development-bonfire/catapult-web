<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class CDISProductUomPackaging extends Model
{
    protected $table = 'cdis_product_uom_packaging';

    protected $fillable = [
        'bid',
        'barcode',
        'description',
        'long_description',
        'product_bid',
        'uom_bid',
        'pack_content',
        'is_menu_item',
        'is_raw_material',
        'is_default',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'product_bid' => 'string',
        'uom_bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];
}
