<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class UsageType extends Enum
{
    const PRODUCT   =   0;
    const ADDON     =   1;
    const BUNDLE    =   2;
    const HEAD_DISCOUNT =   4;
}
