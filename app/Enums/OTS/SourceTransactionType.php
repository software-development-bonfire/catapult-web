<?php

namespace App\Enums\OTS;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class SourceTransactionType extends Enum implements LocalizedEnum
{
    
    const FINEDINE = 13; // This is the identifier from POS for fine dine transactions that is came from Station OTS then processed by POS to send to Kitchen Display System
}
