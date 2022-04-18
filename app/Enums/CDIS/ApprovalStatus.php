<?php

namespace App\Enums\CDIS;

use BenSampo\Enum\Enum;

final class ApprovalStatus extends Enum
{
    const PENDING = 0;
    const APPROVED = 1;
    const APPROVED_UPCOMING = 10;
    const APPROVED_ONGOING = 11;
    const APPROVED_ENDED = 12;
    const APPROVED_CANCELLED = 13;
    const REJECTED = 2;
    const DELETED = 3;
    const PENDING_LAPSED = 4;
}
