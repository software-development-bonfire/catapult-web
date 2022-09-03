<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;

class CDISProductCategory extends BaseModel
{
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

    public function parent()
    {
        return $this->hasMany(CDISProductCategory::class, 'parent_bid', 'bid');
    }

    public function syncDetails()
    {
        $syncDetails = (object) array(
            'code' => null,
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => null,
            'reference_table' => null,
        );

        $routeName = Route::currentRouteName();

        if ($routeName == 'store_product_sub_category') {
            $syncDetails->reference_bid = $this->parent_bid;
            $syncDetails->reference_table = $this->getTable() ?? 'cdis_product_category';
        }

        return $syncDetails;
    }
}
