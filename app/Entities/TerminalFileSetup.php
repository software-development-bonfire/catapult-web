<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class TerminalFileSetup.
 *
 * @package namespace App\Entities;
 */
class TerminalFileSetup extends Model implements Transformable
{
    use TransformableTrait,
        SoftDeletes,
        BidObserverTrait;

    protected $table = 'terminal_file_setups';

    protected $primaryKey = 'bid';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'name',
        'type',
        'api_setup_bid',
        'status',
        'terminal_code',
        'terminal_path',
        'sub_directories',
        'created_by',
        'updated_by',
    ];

    public function apiSetup()
    {
        return $this->belongsTo(ApiSetup::class, 'api_setup_bid', 'bid');
    }
}
