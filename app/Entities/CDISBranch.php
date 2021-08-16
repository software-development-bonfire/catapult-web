<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISBranch extends Base
{
    use SoftDeletes;

    protected $table = 'cdis_branch';

    protected $fillable = [
        'bid',
        'code',
        'name',
        'address',
        'category',
        'contact_person',
        'contact_number',
        'business_name',
        'tin_no',
        'type',
        'status',
        'is_main_branch',
        'start_operation_hour',
        'end_operation_hour',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'is_main_branch' => 'boolean'
    ];
}
