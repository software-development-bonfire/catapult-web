<?php

namespace App\Enums\CDIS;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class VendorCostDiscountType extends Enum implements LocalizedEnum
{
    const ROLLING = 1;
    const CUMULATIVE = 2;
}
