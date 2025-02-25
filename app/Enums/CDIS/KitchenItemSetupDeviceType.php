<?php

namespace App\Enums\CDIS;


use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class KitchenItemSetupDeviceType extends Enum implements LocalizedEnum
{
    const DISPLAY = 1;
    const PRINTER = 2;
}
