<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CDISProductCategory extends Model
{
    use SoftDeletes;

    protected $table = 'cdis_product_category';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'name',
        'button_color',
        'status',
        'parent_bid',
        'level',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'parent_bid' => 'string',
    ];
}
