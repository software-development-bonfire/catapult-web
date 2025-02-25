<?php


return [
    'catapult_port' => env('CATAPULT_PORT', NULL),
    'catapult_key' => env('CATAPULT_KEY', NULL),

    'api' => [
        /*
        |--------------------------------------------------------------------------
        | Defined API scopes allowed to connect
        |--------------------------------------------------------------------------
        */
        'scopes' => [
            'pos' => 'Sirius POS',
            'pda' => 'PDA',
            'kiosk' => 'Kiosk OTS',
            'mobile' => 'QR Mobile',
        ],

        /*
        |--------------------------------------------------------------------------
        | Defined Authentication type of devices
        |--------------------------------------------------------------------------
        | Here you may specify which type of the authentication use on devices
        | to connect to this system. (device, user).
        |
        | user = means you need to provide valid user credential which are username and password
        | device = you must provide a valida device_code which is registered to device_settings to authenticate
        */
        'auth_type' => 'device'
    ],

    'printers' => [
        'sticker' => env('PRINTER_STICKER', NULL),
    ],
];
