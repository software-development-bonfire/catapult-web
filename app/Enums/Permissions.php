<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Permissions extends Enum
{
    const LIST = [
        'view' => [
            'dashboard' => 110101,
            'logs' => 110201,
            'user_account' => 110301,
            'kitchen_device_printer' => 110401,
        ],
        'general.administrator' => 100001,
    ];
}
