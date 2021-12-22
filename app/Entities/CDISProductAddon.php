<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISProductAddon extends BaseModel
{
    use SoftDeletes;

    protected $table = 'cdis_product_addon';

    protected $fillable = [
        'bid',
        'product_uom_bid',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'product_uom_bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    protected $auditExclude = [
        'id',
        'bid',
        'product_uom_bid',
        'created_by',
        'updated_by',
    ];

    public function uomPackaging()
    {
        return $this->belongsTo(CDISProductUomPackaging::class, 'product_uom_bid', 'bid');
    }

    public function detail()
    {
        return $this->hasMany(CDISProductAddonDetail::class, 'head_bid', 'bid');
    }
}
