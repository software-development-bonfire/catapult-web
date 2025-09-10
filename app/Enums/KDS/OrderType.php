<?php

namespace App\Enums\KDS;

use BenSampo\Enum\Enum;

final class OrderType extends Enum
{
    const DINE_IN = 1000000000001;
    const TAKE_OUT = 1000000000002;
    const DELIVERY = 1000000000003;
    const DRIVE_THRU = 1000000000004;
}