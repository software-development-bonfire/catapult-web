<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class UserType extends Enum implements LocalizedEnum
{
    const SUPERADMIN = -1;
    const ADMIN = 0;
    const DEFAULT = 1;
    const MASTER = 2;
}
