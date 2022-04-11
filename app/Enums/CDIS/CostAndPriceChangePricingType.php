<?php

namespace App\Enums\CDIS;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class CostAndPriceChangePricingType extends Enum implements LocalizedEnum
{
    const COST = 1;
    const PRICE = 2;
}