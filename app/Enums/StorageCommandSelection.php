<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class StorageCommandSelection extends Enum
{
    const SYNC          = 1;
    const CONVERT       = 2;
    const SEND          = 3;
    const RESEND        = 4;
    const CDIS_CONVERT  = 5;
    const CDIS_FORWARD  = 6;
}
