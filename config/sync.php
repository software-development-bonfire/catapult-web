<?php

return [
    'restart' => false,

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
    'cdis' => [
        'to_catapult' => [
            'limit' => 300,
            'convert_limit' => 999999999,
            'interval' => 3600,
            'broadcast' => true,
            'progress_divisor' => 100
        ],
        'clear_jobs' => false,
    ],
    'pos' => [
        'to_cdis' => [
            'timeout' => 5,
            'max_retry' => 4,
        ]
    ],
    /*
    |--------------------------------------------------------------------------
    | Scheduling Configuration
    |--------------------------------------------------------------------------
    | These parameters in generating CSV specifically for Cost and Price Change
    |
    | time_interval     : The time interval in "minutes" that the scheduler will run every minutes
    | previous_day      : Number of days to check previous cost and price change entries. The default 
                          value is -1, means all previous data will be included in criteria
    | next_day          : Number of days in advance to check the date of effectivity of cost and price change entries.
    */
    'scheduling' => [
        'time_interval' => 1,
        'previous_day' => -1,
        'next_day' => 2,
    ]
];
