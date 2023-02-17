<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class DisplayState extends Enum implements LocalizedEnum
{
    const NO    = 0;
    const YES   = 1;
}