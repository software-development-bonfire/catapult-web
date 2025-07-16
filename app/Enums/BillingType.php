<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class BillingType extends Enum
{
    const COD = 1;
    const ONLINE_PAYMENT = 2;
}
