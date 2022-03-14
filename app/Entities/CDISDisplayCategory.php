<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISDisplayCategory extends BaseModel
{
    use SoftDeletes;

    protected $table = 'cdis_display_category';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'code',
        'display_category',
        'display_priority',
        'status',
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
}
