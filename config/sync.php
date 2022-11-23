<?php

return [
    'restart' => false,
    'cdis' => [
        'to_catapult' => [
            'limit' => 300,
            'interval' => 3600,
            'broadcast' => true,
            'progress_divisor' => 1000
        ]
    ],
    'pos' => [
        'to_cdis' => [
            'timeout' => 5,
            'max_retry' => 4,
        ]
    ],
];
