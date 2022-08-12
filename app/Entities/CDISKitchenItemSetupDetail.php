<?php

namespace App\Entities;

class CDISKitchenItemSetupDetail extends BaseModel
{
    protected $table = 'cdis_kitchen_item_setup_detail';

    protected $fillable = [
        'bid',
        'head_bid',
        'kitchen_station_process_bid',
        'product_uom_packaging_bid',
        'created_by',
        'updated_by',

    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'kitchen_station_process_bid' => 'string',
        'product_uom_packaging_bid' => 'string',
    ];

    public function kitchenItemSetup()
    {
        return $this->belongsTo(CDISKitchenItemSetup::class, 'head_bid', 'bid');
    }

    public function kitchenStationProcess()
    {
        return $this->belongsTo(CDISKitchenStationProcess::class, 'kitchen_station_process_bid', 'bid');
    }

    public function productUomPackaging()
    {
        return $this->belongsTo(CDISProductUomPackaging::class, 'product_uom_packaging_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => json_encode([$this->head_bid, $this->kitchen_station_process_bid, $this->product_uom_packaging_bid]),
            'reference_table' => json_encode(['cdis_kitchen_item_setup', 'cdis_kitchen_station_process', 'cdis_product_uom_packaging']),
        );
    }
}
