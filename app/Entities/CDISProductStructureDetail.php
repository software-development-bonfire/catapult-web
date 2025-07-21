<?php

namespace App\Entities;

class CDISProductStructureDetail extends BaseModel
{
    protected $table = 'cdis_product_structure_detail';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'head_bid',
        'product_uom_bid',
        'quantity',
        'classification',
        'is_reduce_composition',
        'is_display_structure',
        'is_include_on_reports',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'product_uom_bid' => 'string',
    ];

    public function productUomPackaging()
    {
        return $this->hasManyThrough(
            CDISProductUomPackaging::class,
            CDISProductStructure::class,
            'bid',
            'bid',
            'head_bid',
            'product_uom_bid');
    }

    public function productUomPackagingByProductUomBid()
    {
        return $this->belongsTo(CDISProductUomPackaging::class, 'product_uom_bid', 'bid');
    }

    public function productStructure()
    {
        return $this->belongsTo(CDISProductStructure::class, 'head_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => '',
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => json_encode([$this->head_bid, $this->product_uom_bid]),
            'reference_table' => json_encode(['cdis_product_structure', 'cdis_product_uom_packaging']),
        );
    }
}
