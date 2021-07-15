<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;

class CDISBrand extends Model
{
    use BidObserverTrait;

    protected $table = 'cdis_brand';

    protected $fillable = [
        'bid',
        'code',
        'name',
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
}
