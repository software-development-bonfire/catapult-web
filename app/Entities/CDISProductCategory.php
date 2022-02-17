<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISProductCategory extends BaseModel
{
    use SoftDeletes;

    protected $table = 'cdis_product_category';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'name',
        'button_color',
        'status',
        'parent_bid',
        'level',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'parent_bid' => 'string',
    ];

    public function product()
    {
        return $this->hasMany(CDISProduct::class, 'category_bid', 'bid');
    }
}
