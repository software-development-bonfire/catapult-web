<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;

class CDISProduct extends BaseModel
{
    protected $table = 'cdis_product';

    protected $fillable = [
        'bid',
        'item_code',
        'category_bid',
        'brand_bid',
        'status',
        'is_sell_item',
        'is_inventory_item',
        'tax_code',
        'is_senior_item',
        'is_pwd_item',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'category_bid' => 'string',
        'item_code' => 'string',
        'brand_bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function brand()
    {
        return $this->belongsTo(CDISBrand::class, 'brand_bid', 'bid');
    }

    public function productUomPackaging()
    {
        return $this->hasMany(CDISProductUomPackaging::class, 'product_bid', 'bid');
    }

    public function productCategory()
    {
        return $this->belongsTo(CDISProductCategory::class, 'category_bid', 'bid');
    }

    public function syncDetails()
    {
        $syncDetails = (object) array(
            'code' => null,
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => null, 
            'reference_table' => null,
        );

        $routeName = Route::currentRouteName();

        if ($routeName == 'create_product' || $routeName == 'destroy_product') {
            $syncDetails->group = $this->getTable() ?? 'cdis_product';
            $syncDetails->head_bid = $this->bid;
        }

        $productCategoryTableName = "cdis_product_category";
        if ($this->productCategory !== null) {
            $productCategoryTableName = $this->productCategory->getTable();
        }

        $brandTableName = "cdis_brand";
        if ($this->brand !== null) {
            $brandTableName = $this->brand->getTable();
        }

        $syncDetails->reference_bid = json_encode([$this->category_bid, $this->brand_bid]);
        $syncDetails->reference_table = json_encode([$productCategoryTableName, $brandTableName]);

        return $syncDetails;
    }
}
