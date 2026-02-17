<?php

namespace App\Enums\KDS;

use BenSampo\Enum\Enum;

final class KDSMovementAction extends Enum
{
    const RELEASE   = 1;
    const DONE      = 2;
    const ADD       = 3;
    const UPDATE    = 4;
    const DELETE    = 5;
}
