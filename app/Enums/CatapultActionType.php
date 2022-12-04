<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static NEW_BRANCH
 * @method static static EVENT
 * @method static static CHANGES
 * @method static static ALL
 */
final class CatapultActionType extends Enum
{
    const NEW_BRANCH    = 'NEW_BRANCH';
    const EVENT         = 'EVENT';
    const CHANGES       = 'CHANGES';
    const ALL           = 'ALL';
}
