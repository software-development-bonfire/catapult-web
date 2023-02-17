<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class CDISZreadRegularDiscount.
 *
 * @package namespace App\Entities;
 */
class CDISZreadRegularDiscount extends Model implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    protected $table = 'cdis_zread_regular_discount';

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

    public function head()
    {
        return $this->belongsTo(CDISZread::class, 'head_bid', 'bid');
    }
}
