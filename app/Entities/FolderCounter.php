<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class FolderCounter.
 *
 * @package namespace App\Entities;
 */
class FolderCounter extends Model implements Transformable
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
        'branch_bid',
        'mapping_type',
        'counter',
    ];

    protected $casts = [
        'bid' => 'string',
    ];

}
