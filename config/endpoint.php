<?php

return [
    'cdis' => [
        'domain' => env('CDIS_URL', 'http://localhost'),
        'for' => [
            'catapult' => [
                'v1' => [
                    'forSync' => '/api/catapult/v1/for-sync',
                    'sync' => '/api/catapult/v1/sync',
                    'deleteSynced' => '/api/catapult/v1/delete-synced'
                ]
            ],
        ]
    ]
];
