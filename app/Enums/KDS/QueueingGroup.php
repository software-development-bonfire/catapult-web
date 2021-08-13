<?php

namespace App\Enums\KDS;

use BenSampo\Enum\Enum;

final class QueueingGroup extends Enum
{
    const PREPARING = 1;
    const FINISHING = 2;
    const RELEASING = 3;
}
