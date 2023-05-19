<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Offline
 * @method static static Online
 * @method static static Fetching
 * @method static static Fetched
 * @method static static Syncing
 * @method static static SyncDone
 * @method static static Converting
 * @method static static ConversionDone
 */
final class CatapultSyncStatus extends Enum
{
    const Generating        = 'Generating';
    const Subscribed        = 'Subscribed';
    const Offline           = 'Offline';
    const Online            = 'Online';
    const Fetching          = 'Fetching';
    const Fetched           = 'Fetched';
    const Syncing           = 'Syncing';
    const SyncDone          = 'SyncDone';
    const Converting        = 'Converting';
    const ConversionDone    = 'ConversionDone';
    const CancelConversion  = 'CancelConversion';
    const CancelSyncing     = 'CancelSyncing';
    const PongCatapult      = 'PongCatapult';
    const Progress          = 'Progress';
    const ProgressDone      = 'ProgressDone';
    const Resyncing         = 'Resyncing';
    const Resynced          = 'Resynced';
}
