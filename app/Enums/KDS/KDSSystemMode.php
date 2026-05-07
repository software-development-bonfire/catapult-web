<?php

namespace App\Enums\KDS;

use BenSampo\Enum\Enum;

/**
 * KDS System Mode
 *
 * Integer values used for broadcast payloads (Flutter expects 0/1).
 * Use DB_FAST_FOOD / DB_FINE_DINE for database storage.
 */
final class KDSSystemMode extends Enum
{
    // Broadcast payload values (Flutter SystemMode.fromValue)
    const FAST_FOOD = 0;
    const FINE_DINE = 1;

    // Database ENUM values (kitchen_display.system_mode)
    const DB_FAST_FOOD = 'FASTFOOD';
    const DB_FINE_DINE = 'FINEDINE';
}
