<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISTags extends BaseModel
{
    protected $table = 'cdis_tags';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'name',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string'
    ];

    protected $auditExclude = [
        'id',
        'bid',
        'created_by',
        'updated_by',
    ];

}
