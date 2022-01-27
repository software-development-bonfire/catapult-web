<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Disk extends Enum
{
    const LOCAL_POS_TO_CDIS = "local-pos-to-cdis";
    const POS_TO_CDIS = "pos-to-cdis";
    const DEFAULT_CSV = "default-csv";
}
