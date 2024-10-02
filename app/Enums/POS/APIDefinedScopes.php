<?php

namespace App\Enums\POS;

use BenSampo\Enum\Enum;

final class APIDefinedScopes extends Enum
{
    const SCOPES = [
        'pos' => 'Sirius POS',
        'pda' => 'PDA',
        'kiosk' => 'Kiosk OTS',
        'mobile' => 'QR Mobile',
        'kds' => 'Kitchen Display',
        'queueing' => 'Queueing System',
    ];
}
