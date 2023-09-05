<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class ErrorLog.
 *
 * @package namespace App\Entities;
 */
class ErrorLog extends Model implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    protected $primaryKey = 'bid';

    public $incrementing = false;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'pos_entry',
        'filename',
        'path',
        'status',
        'updated_at'
    ];

    public function details() {
        return $this->hasMany(ErrorLogDetail::class, 'error_log_bid', 'bid');
    }

}
