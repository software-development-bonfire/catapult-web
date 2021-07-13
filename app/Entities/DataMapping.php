<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class DataMapping.
 *
 * @package namespace App\Entities;
 */
class DataMapping extends Model implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'field_mapping_list_bid',
        'required',
        'field',
        'description',
        'mapping_type',
        'file_name',
        'default_value',
        'column_name',
    ];

}
