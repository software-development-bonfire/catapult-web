<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class CDISZreadTenderDetails.
 *
 * @package namespace App\Entities;
 */
class CDISZreadTenderDetail extends Model implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    protected $table = 'cdis_zread_tender_detail';

    public $incrementing = false;

    protected $fillable = [
        'head_bid',
        'name',
        'count',
        'amount',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
    ];

}
