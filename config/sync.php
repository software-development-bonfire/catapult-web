<?php

/*
|--------------------------------------------------------------------------
| Default Command parameters in Catapult
|--------------------------------------------------------------------------
| These parameters will automatically the value of console command
| parameters if there are no payload data options provided in CDIS events
|
| limit             : This will be the limit to be fetched and synced to Catapult, means every limit will be fetched
|                     and synced then deleted in [sync] table (CDIS) and then proceed to another chunk of bids in [sync] table.
| convert_limit     : This will be the maximum limit value to be converted, used in conversion process.
| broadcast         : Set to True if you want to broadcast the process. Helps to show activity message in Developer Options.
| show_progress     : Set to True if you want to show progress in Catapult process such syncing and conversion.
|                     Note: this will be ignored when options.broadcast was set to False
| progress_divisor  : If options.show_progress is False, then it will automatically applied to show progress
|                     every progress_divisor. It helps to control usage of Pusher message request
*/

return [
    'restart' => false,
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
    'scheduling' => [
        'time_interval' => 2,
    ]
];
