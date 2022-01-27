<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class CDISCashDrawer.
 *
 * @package namespace App\Entities;
 */
class CDISCashDrawer extends BaseModel implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    protected $table = 'cdis_cash_drawer';

    public $incrementing = false;

    protected $fillable = [
        'terminal_bid',
        'cashier_bid',
        'cashier_name',
        'amount',
        'date',
        'approver_bid',
        'approver_name',
        'approved_date',
        'type',
        'remarks',
    ];

    protected $casts = [
        'bid' => 'string',
        'terminal_bid' => 'string',
        'cashier_bid' => 'string',
        'approver_bid' => 'string',
    ];
}
