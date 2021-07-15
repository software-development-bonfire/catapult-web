<?php

namespace App\Entities;


use Illuminate\Database\Eloquent\Model;

class CDISProductStructure extends Model
{
    protected $table = 'cdis_product_structure';

    protected $fillable = [
        'bid',
        'product_uom_bid',
        'valid_from',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'product_uom_bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];
}
