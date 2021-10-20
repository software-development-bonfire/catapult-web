<?php

namespace App\Entities;


use App\Enums\KDS\DeviceType;

class CDISKitchenItemSetup extends BaseModel
{
    protected $table = 'cdis_kitchen_item_setup';

    protected $fillable = [
        'bid',
        'code',
        'branch_bid',
        'device_type_bid',
        'status'
    ];

    protected $casts = [
        'bid' => 'string',
        'device_type_bid' => 'string',
        'branch_bid' => 'string',
    ];

    public function productUomPackaging()
    {
        return $this->hasManyThrough(CDISProductUomPackaging::class, CDISKitchenItemSetupDetail::class, 'product_uom_packaging_bid', 'bid','bid', 'head_bid');
    }

    public function kitchenDevicePrinter()
    {
        return $this->hasOne(CDISKitchenDevicePrinter::class, 'bid', 'device_type_bid');
    }
}
