<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISProductVariantOption extends BaseModel
{
    protected $table = 'cdis_product_variant_option';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'head_bid',
        'name',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
    ];

    protected $auditExclude = [
        'id',
        'bid',
        'created_by',
        'updated_by',
    ];

    public function productVariant()
    {
        return $this->belongsTo(CDISProductVariant::class, 'head_bid','bid');
    }
}
