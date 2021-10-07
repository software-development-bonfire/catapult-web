<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class StorageType extends Enum implements LocalizedEnum
{
    const LOCAL_NETWORK = 0;
    const FTP = 1;
}
