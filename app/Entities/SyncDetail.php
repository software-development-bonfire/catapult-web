<?php

namespace App\Entities;


class SyncDetail extends Base
{
    protected $table = 'sync_details';

    protected $primaryKey = 'bid';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'code',
        'state',
        'description',
        'sync_entry',
        'online_at',
    ];

    protected $casts = [
        'bid' => 'string',
    ];
}
