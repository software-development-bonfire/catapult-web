<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISUnitOfMeasurement extends BaseModel
{
    protected $table = 'cdis_unit_of_measurement';

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
