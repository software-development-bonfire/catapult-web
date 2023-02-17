<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISBrand extends BaseModel
{
    protected $table = 'cdis_brand';

    protected $fillable = [
        'bid',
        'code',
        'name',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    protected $auditExclude = [
        'id',
        'bid',
        'created_by',
        'updated_by',
    ];

    public function productUomPackaging()
    {
        return $this->hasMany(CDISProductUomPackaging::class, 'brand_bid', 'bid');
    }
    
    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => null,
            'head_bid' => null,
            'level' => 1,
        );
    }
}
