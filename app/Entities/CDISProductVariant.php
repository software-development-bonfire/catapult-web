<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISProductVariant extends Base
{
    use SoftDeletes;

    protected $table = 'cdis_product_variant';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'code',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
    ];

    protected $auditExclude = [
        'id',
        'bid',
        'created_by',
        'updated_by',
    ];

    public function options()
    {
        return $this->hasMany(CDISProductVariantOption::class, 'head_bid','bid');
    }
}
