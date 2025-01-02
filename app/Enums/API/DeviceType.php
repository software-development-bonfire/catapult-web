<?php

namespace App\Enums\API;

use BenSampo\Enum\Enum;

final class DeviceType extends Enum
{
    const SIRIUS_POS    =  1;
    const PDA           =  2;
    const KIOSK         =  3;
    const QR_MOBILE     =  4;
    const KDS           =  5;
    const QUEUEING      =  6;
}
