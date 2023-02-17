<?php

namespace App\Enums\CDIS;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class TaxCode extends Enum implements LocalizedEnum
{
    const VATABLE = 0;
    const NON_VATABLE = 1;
    const ZERO_RATED = 2;
}
