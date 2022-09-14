<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ErrorStatus extends Enum
{
    const CONVERSION_ERROR  = "CONVERSION ERROR";
    const SYNCING_ERROR     = "SYNCING ERROR";
}
