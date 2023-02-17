<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISProductModifier extends BaseModel
{
    protected $table = 'cdis_product_modifier';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'name',
        'product_uom_bid',
        'description',
        'modifier_type',
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

    public function productModifierDetail()
    {
        return $this->hasMany(CDISProductModifierDetail::class, 'head_bid', 'bid');
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
