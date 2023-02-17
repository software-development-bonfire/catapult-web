<?php

namespace App\Enums\KDS;

use BenSampo\Enum\Enum;

final class OrderType extends Enum
{
    const DINE_IN = 1;
    const TAKE_OUT = 2;
    const DELIVERY = 3;
    const DRIVE_THRU = 4;
}
