<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ApiEndpoint extends Enum
{
    const TRANSACTION = "Transactions";
    const ZREAD = "Zread";
    const AUDIT_TRAIL = "Audit Trail";
    const CASH_BREAKDOWN = "Cash Breakdown";
    const CASH_DRAWER = "Cash Drawer";
}
