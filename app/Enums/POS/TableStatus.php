<?php

namespace App\Enums\POS;

use BenSampo\Enum\Enum;

final class TableStatus extends Enum
{
    const AVAILABLE = 0;
    const OCCUPIED  = 1;
    const RESERVED  = 2;
}
