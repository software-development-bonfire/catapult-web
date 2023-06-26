<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class DefinedQueueName extends Enum
{
    const SYNC      = 'sync';
    const CONVERT   = 'convert';
}
