<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISProductUomPackaging extends BaseModel
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
        'is_finished_good',
        'is_display_structure',
        'is_menu_item',
        'is_raw_material',
        'is_addon',
        'is_sell_item',
        'is_inventory_item',
        'is_senior_item',
        'is_pwd_item',
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

    public function kitchenItemSetupDetail()
    {
        return $this->hasMany(CDISKitchenItemSetupDetail::class, 'product_uom_packaging_bid', 'bid');
    }

    public function kitchenItemSetup()
    {
        return $this->hasManyThrough(CDISKitchenItemSetup::class, CDISKitchenItemSetupDetail::class, 'product_uom_packaging_bid', 'bid','bid', 'head_bid');
    }

    public function product()
    {
        return $this->belongsTo(CDISProduct::class, 'product_bid', 'bid');
    }

    public function productBranchAvailability()
    {
        return $this->hasMany(CDISProductBranchAvailability::class, 'product_uom_bid', 'bid');
    }

    public function productStructureDetail()
    {
        return $this->hasMany(CDISProductStructureDetail::class, 'product_uom_bid', 'bid');
    }

    public function productAddonDetail()
    {
        return $this->hasMany(CDISProductAddonDetail::class, 'product_uom_bid', 'bid');
    }

    public function productModifier()
    {
        return $this->hasMany(CDISProductModifier::class, 'product_uom_bid', 'bid');
    }

    public function productModifierDetail()
    {
        return $this->hasMany(CDISProductModifierDetail::class, 'product_uom_bid', 'bid');
    }
}
