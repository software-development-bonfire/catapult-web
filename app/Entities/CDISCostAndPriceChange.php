<?php

namespace App\Entities;

class CDISCostAndPriceChange extends BaseModel
{
    protected $table = 'cdis_cost_and_price_change';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'code',
        'pricing_type',
        'type',
        'vendor_bid',
        'effective_at',
        'expires_at',
        'status',
        'is_immediate',
        'assessed_by',
        'assessed_at',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'is_generated',
        'generated_at'
    ];

    protected $casts = [
        'bid' => 'string',
        'vendor_bid' => 'string',
        'category_bid' => 'string',
        'code' => 'string',
        'assessed_by' => 'string',
        'approved_by' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function details() 
    {
        return $this->hasMany(CDISCostAndPriceChangeDetail::class, 'head_bid', 'bid');
    }

    public function vendor()
    {
        return $this->belongsTo(CDISVendor::class, 'vendor_bid', 'bid');
    }

    public function category()
    {
        return $this->belongsTo(CDISProductCategory::class, 'category_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => '',
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => json_encode([$this->vendor_bid, $this->category_bid]),
            'reference_table' => json_encode(['cdis_vendor', 'cdis_product_category'])
        );
    }
}
