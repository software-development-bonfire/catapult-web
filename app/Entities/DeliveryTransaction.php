<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class DeviceSettings.
 *
 * @package namespace App\Entities;
 */
class DeliveryTransaction extends Base
{
    use SoftDeletes;
    protected $table = 'delivery_transaction';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'pos_terminal_transaction_bid',
        'email_address',
        'contact_number',
        'address',
        'no_bldng_lot_street',
        'delivery_instruction',
        'created_by',
    ];

    protected $casts = [
        'bid' => 'string',
    ];

}
