<?php

namespace App\Enums\KDS;

use BenSampo\Enum\Enum;

final class MenuStatus extends Enum
{
    const REFUNDED = -1;
    const WAITING = 0;
    const ON_PROCESS = 1;
    const DELETED = 2;
    const DONE = 3;
    const RELEASING = 4;
    const ASSEMBLY = 5;
}
