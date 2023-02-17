<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class InventoryLocationTagType extends Enum implements LocalizedEnum
{
    const BRANCH = 0;
    const TERMINAL = 1;
}
