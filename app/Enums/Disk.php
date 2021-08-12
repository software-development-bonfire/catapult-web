<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Disk extends Enum
{
    const LOCAL_POS_TO_CDIS = "local-pos-to-cdis";
    const FTP_POST_TO_CDIS = "ftp-pos-to-cdis";
    const DEFAULT_CSV = "default-csv";
    const SYSTEM_LOGS = "system-logs";
}
