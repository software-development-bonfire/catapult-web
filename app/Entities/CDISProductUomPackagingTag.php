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
        return $this->belongsTo(CDISTags::class, 'tag_bid', 'bid');
    }

    public function syncDetails()
    {
        $code = '';
        $group = $this->getTable() ?? 'cdis_product_uom_packaging_tag';
        $headBid = $this->product_uom_packaging_bid;
        $level = 1;

        return (object) array(
            'code' => $code,
            'group' => $group,
            'head_bid' => $headBid,
            'level' => $level,
            'reference_bid' => json_encode([$this->product_uom_packaging_bid, $this->tag_bid]),
            'reference_table' => json_encode(['cdis_product_uom_packaging', 'cdis_tags'])
        );
    }

}
