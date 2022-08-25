<?php

return [
    'restart' => false,
    'cdis' => [
        'to_catapult' => [
            'limit' => 300,
            'interval' => 3600,
            'broadcast' => true,
        ]
    ],
    'pos' => [
        'to_cdis' => [
            'timeout' => 5,
        ]
    ],
];
