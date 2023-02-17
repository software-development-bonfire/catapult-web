<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISProductAddon extends BaseModel
{
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

    public function productUomPackaging()
    {
        return $this->belongsTo(CDISProductUomPackaging::class, 'product_uom_bid', 'bid');
    }

    public function productAddonDetail()
    {
        return $this->hasMany(CDISProductAddonDetail::class, 'head_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => '',
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => json_encode([$this->product_uom_bid]),
            'reference_table' => json_encode(['cdis_product_uom_packaging'])
        );
    }
}
