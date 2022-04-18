<?php

namespace App\Enums\CDIS;

use BenSampo\Enum\Enum;

final class CostAndPriceChangeType extends Enum
{
    const PERMANENT = 1;
    const TIME_TRIGGER = 2;
}