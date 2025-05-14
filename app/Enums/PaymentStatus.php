<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class PaymentStatus extends Enum
{
    const CREATED = 0;
    const PAID =   1;
    const FAILED =   2;
    const CANCELED = 3;
}
