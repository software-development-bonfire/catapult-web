<?php

return [
    'restart' => false,

     /*
    |--------------------------------------------------------------------------
    | Default Command parameters in CDIS to POS
    |--------------------------------------------------------------------------
    | These parameters will automatically the value of console command
    | parameters if there are no payload data options provided in CDIS events
    |
    | 'limit' is the number of bids in a chunks to be synced
    |
    */
    'cdis' => [
        'to_catapult' => [
            'limit' => 300,
            'convert_limit' => 999999999,
            'interval' => 3600,
            'broadcast' => true,
            'progress_divisor' => 100
        ]
    ],
    'pos' => [
        'to_cdis' => [
            'timeout' => 5,
            'max_retry' => 4,
        ]
    ],
];
