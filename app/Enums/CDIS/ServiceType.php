<?php

namespace App\Enums\CDIS;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class ServiceType extends Enum
{
    const RETAIL = 1;
    const FOOD_AND_BEVERAGES = 2;
    const DRUGSTORE = 3;
}
