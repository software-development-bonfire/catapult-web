<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class ErrorLogDetail.
 *
 * @package namespace App\Entities;
 */
class ErrorLogDetail extends Model implements Transformable
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
        'error_log_bid',
        'sheet',
        'error_type',
        'description',
        'updated_at'
    ];

}
