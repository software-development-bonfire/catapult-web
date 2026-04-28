<?php

namespace App\Enums\POS;

use BenSampo\Enum\Enum;

final class DeviceMode extends Enum
{
    const FAST_FOOD     = 1;
    const FINE_DINE     = 2;
    const STATION_OTS   = 3;
}
