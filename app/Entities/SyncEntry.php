<?php

namespace App\Entities;


class SyncEntry extends Base
{
    protected $table = 'sync_entry';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'alias',
        'type',
    ];

    public function detail()
    {
        return $this->hasMany(SyncEntryDetail::class, 'head_bid', 'bid');
    }
}
