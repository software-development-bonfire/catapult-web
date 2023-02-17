<?php

namespace App\Enums\CDIS;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class DiscountSettingsMethod extends Enum implements LocalizedEnum
{
    const SENIOR_FOOD = 1;
    const SENIOR_RETAIL = 2;
    const PWD_FOOD = 3;
    const PWD_RETAIL = 4;
    const REGULAR = 5;
}
