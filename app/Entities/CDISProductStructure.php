<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISProductStructure extends BaseModel
{
    protected $table = 'cdis_product_structure';

    protected $fillable = [
        'bid',
        'product_uom_bid',
        'valid_from',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'product_uom_bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function productUomPackaging()
    {
        return $this->belongsTo(CDISProductUomPackaging::class, 'product_uom_bid', 'bid');
    }

    public function productStructureDetail()
    {
        return $this->hasMany(CDISProductStructureDetail::class, 'head_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => '',
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => null,
            'reference_table' => null,
        );
    }
}
