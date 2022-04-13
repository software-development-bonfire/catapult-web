<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISProductBranchAvailability extends BaseModel
{
    protected $table = 'cdis_product_branch_availability';

    protected $fillable = [
        'bid',
        'branch_bid',
        'product_uom_bid',
        'is_available',
        'min_stock',
        'max_stock',
        'created_by',
        'updated_by',
        'deleted_at'
    ];

    protected $casts = [
        'bid' => 'string',
        'branch_bid' => 'string',
        'product_uom_bid' => 'string',
        'is_available' => 'boolean',
    ];

    public function productBranchPrice()
    {
        return $this->hasMany(CDISProductBranchPrice::class, 'product_branch_availability_bid', 'bid');
    }

    public function productUomPackaging()
    {
        return $this->belongsTo(CDISProductUomPackaging::class, 'product_uom_bid', 'bid');
    }
}
