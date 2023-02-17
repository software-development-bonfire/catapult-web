<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class DeleteSyncedAction extends Enum
{
    const CONVERT       = 'CONVERT';
    const CONVERT_ALL   = 'CONVERT_ALL_DATA';
    const CONVERT_EVENT = 'CONVERT_CHANGES';
    const SYNC_DONE     = 'SYNC_DONE';
}
