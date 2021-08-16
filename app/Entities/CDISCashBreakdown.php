<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class CDISCashBreakdown.
 *
 * @package namespace App\Entities;
 */
class CDISCashBreakdown extends Model implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    protected $table = 'cdis_cash_breakdown';

    public $incrementing = false;

    protected $fillable = [
        'terminal_bid',
        'cashier_bid',
        'cashier_name',
        'date',
        'approver_bid',
        'approver_name',
        'approved_date',
        'remarks',
    ];

    protected $casts = [
        'bid' => 'string',
        'terminal_bid' => 'string',
        'cashier_bid' => 'string',
        'approver_bid' => 'string',
    ];

}
