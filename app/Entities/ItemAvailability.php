<?php

namespace App\Entities;
use App\Traits\BidObserverTrait;


class ItemAvailability extends Base
{
    protected $table = 'item_availability';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'product_uom_bid',
        'item_code',
        'barcode',
        'description',
        'long_description'
    ];

    protected $casts = [
        'bid' => 'string',
        'product_uom_bid' => 'string',
    ];

    public function detail()
    {
        return $this->hasMany(ItemAvailabilityDetail::class, 'head_bid', 'bid');
    }

    public function itemAvailabilityDetail()
    {
        return $this->hasMany(ItemAvailabilityDetail::class, 'head_bid', 'bid');
    }
}
