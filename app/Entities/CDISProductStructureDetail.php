<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class CDISProductStructureDetail extends Model
{
    protected $table = 'cdis_product_structure_detail';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'head_bid',
        'product_uom_bid',
        'quantity',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'product_uom_bid' => 'string',
    ];
}
