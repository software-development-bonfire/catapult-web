<?php

namespace App\Enums\KDS;

use BenSampo\Enum\Enum;

final class MenuStatus extends Enum
{
    const REFUNDED = -1;
    const ON_PROCESS = 1;
    const DELETED = 2;
}
