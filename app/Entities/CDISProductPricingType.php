<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;

class CDISProductPricingType extends BaseModel
{
    use BidObserverTrait;

    protected $table = 'cdis_product_pricing_type';

    protected $primaryKey = 'bid';

    protected $casts = [
        'bid' => 'string',
    ];

    protected $fillable = [
        'bid',
        'name',
        'alias',
        'display_priority',
        'status',
        'created_by',
        'updated_by',
    ];
}
