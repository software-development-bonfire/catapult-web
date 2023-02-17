<?php

namespace App\Enums\CDIS;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class BranchType extends Enum implements LocalizedEnum
{
    const COMPANY_OWNED = 1;
    const FRANCHISED = 2;
}
