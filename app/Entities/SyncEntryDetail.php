<?php

namespace App\Entities;


class SyncEntryDetail extends Base
{
    protected $table = 'sync_entry_detail';

    public $timestamps = false;

    protected $fillable = [
        'head_bid',
        'name',
        'alias',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
    ];

    public function head()
    {
        return $this->belongsTo(SyncEntry::class, 'head_bid', 'bid');
    }
}
