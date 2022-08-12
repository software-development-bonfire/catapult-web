<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class CDISPOSAuditTrail.
 *
 * @package namespace App\Entities;
 */
class CDISPOSAuditTrail extends BaseModel implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    protected $table = 'cdis_pos_audit_trail';

    public $incrementing = false;

    protected $fillable = [
        'terminal_bid',
        'log_id',
        'date',
        'application',
        'cashier',
        'supervisor',
        'job',
        'transaction_no',
        'receipt_no',
        'remarks'
    ];

    protected $casts = [
        'bid' => 'string',
        'terminal_bid' => 'string',
        'transaction_no' => 'string',
        'receipt_no' => 'string',
    ];

    public function terminal()
    {
        return $this->belongsTo(CDISTerminal::class, 'terminal_bid', 'bid');
    }

}
