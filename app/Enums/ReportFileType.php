<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ReportFileType extends Enum
{
    const SALES_TRANSACTIONS    = 1;
    const JOURNAL_REPORTS       = 2;
    const OTHER_REPORTS         = 3;
    const Z_READING             = 4;
}
