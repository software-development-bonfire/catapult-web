<?php

namespace App\Enums\CDIS;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class CustomerType extends Enum implements LocalizedEnum
{
    const WALK_IN = 0;
    const REGULAR = 1;
}
