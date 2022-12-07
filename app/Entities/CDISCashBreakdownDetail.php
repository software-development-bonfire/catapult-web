<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class CDISCashBreakdownDetail.
 *
 * @package namespace App\Entities;
 */
class CDISCashBreakdownDetail extends BaseModel implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    protected $table = 'cdis_cash_breakdown_detail';

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

    public function head()
    {
        return $this->belongsTo(CDISCashBreakdown::class, 'head_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => null,
            'head_bid' => null,
            'level' => 1,
        );
    }
}
