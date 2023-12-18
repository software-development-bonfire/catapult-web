<?php

namespace App\Entities;
use App\Traits\BidObserverTrait;


class ItemAvailabilityDetail extends Base
{
    protected $table = 'item_availability_detail';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'head_bid',
        'device_settings_bid',
        'is_available',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'device_settings_bid' => 'string',
    ];

    public function itemAvailability()
    {
        return $this->belongsTo(ItemAvailability::class, 'head_bid', 'bid')->withDefault(['product_uom_bid' => '']);
    }

    public function device()
    {
        return $this->belongsTo(DeviceSettings::class, 'device_settings_bid', 'bid')->withDefault(['name' => '']);
    }
}
