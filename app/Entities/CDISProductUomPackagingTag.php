<?php

namespace App\Entities;

class CDISProductUomPackagingTag extends BaseModel
{
    protected $table = 'cdis_product_uom_packaging_tag';

    protected $fillable = [
        'bid',
        'product_uom_packaging_bid',
        'tag_bid',
    ];

    protected $casts = [
        'bid' => 'string',
        'product_uom_packaging_bid' => 'string',
        'tag_bid' => 'string',
    ];

    public function productUomPackaging()
    {
        return $this->belongsTo(CDISProductUomPackaging::class, 'product_uom_packaging_bid', 'bid');
    }

    public function tags()
    {
        return $this->belongsTo(CDISTag::class, 'tag_bid', 'bid');
    }

}
