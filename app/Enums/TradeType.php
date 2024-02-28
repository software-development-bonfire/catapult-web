<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TradeType extends Enum
{
    const TRADE_OR_NON_TRADE = 1;
    const TRADE = 2;
    const NON_TRADE = 3;
}
