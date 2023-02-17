<?php

namespace App\Enums\CDIS;

use BenSampo\Enum\Enum;

final class TransactionType extends Enum
{
    const POS = 1;
    const WEB = 0;
}
