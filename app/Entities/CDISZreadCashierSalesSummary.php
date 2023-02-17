<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class CDISZreadCashierSalesSummary.
 *
 * @package namespace App\Entities;
 */
class CDISZreadCashierSalesSummary extends Model implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    protected $table = 'cdis_zread_cashier_sales_summary';

    public $incrementing = false;

    protected $fillable = [
        'head_bid',
        'name',
        'amount',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
    ];

    public function head()
    {
        return $this->belongsTo(CDISZread::class, 'head_bid', 'bid');
    }
}
