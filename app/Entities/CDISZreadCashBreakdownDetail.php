<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class CDISZreadCashBreakdownDetail.
 *
 * @package namespace App\Entities;
 */
class CDISZreadCashBreakdownDetail extends Model implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    protected $table = 'cdis_zread_cash_breakdown_detail';

    public $incrementing = false;

    protected $fillable = [
        'head_bid',
        'denomination',
        'quantity',
        'amount',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
    ];

}
