<?php

namespace App\Enums\KDS;

use BenSampo\Enum\Enum;

final class KDSActionType extends Enum
{
    const FOR_PREPARE = 1;
    const FOR_SERVE  = 2;
    const FOR_BUMP  = 3;
    const FOR_ASSEMBLY  = 4;
    const FOR_RECALL  = 5;
    const FOR_DONE  = 6;

}
