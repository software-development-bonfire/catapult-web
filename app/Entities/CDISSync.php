<?php

namespace App\Entities;


use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;

class CDISSync extends Model
{
    use BidObserverTrait;

    protected $table = 'cdis_sync';

    protected $primaryKey = 'bid';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'branch_bid',
        'table_bid',
        'table_name',
        'level',
        'group',
        'code',
        'action',
        'created_at',
    ];

    protected $casts = [
        'bid' => 'string',
        'branch_bid' => 'string',
        'table_bid' => 'string',
    ];

    public function branch()
    {
        return $this->belongsTo(CDISBranch::class, 'branch_bid', 'bid');
    }
}
