<?php

namespace App\Enums\CDIS;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class TerminalTransactionType extends Enum implements LocalizedEnum
{
    const SALES = 0;
    const REFUND = 1;
    const TRANSACTION_VOID = 2;
    const ITEM_VOID = 3;
    const UNSETTLED = 4;
    const READING = 5;
    const FREE_ITEMS = 6;
    const NO_SALE = 7;
}
