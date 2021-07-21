<?php

namespace App\Entities;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CDISProductStructure extends Model
{
    use SoftDeletes;

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
