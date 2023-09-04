<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class DefinedDashboardCardList extends Enum
{
    const LIST = [
        [
            'slug' => 'to_convert',
            'header' => 'To convert',
            'description' => 'CSV to convert to JSON',
        ],
        [
            'slug' => 'to_sync',
            'header' => 'To sync',
            'description' => 'JSON files to send to CDIS',
        ],
        [
            'slug' => 'to_fetch',
            'header' => 'To fetch',
            'description' => 'file from POS queued for conversion',
        ],
        [
            'slug' => 'failed_conversion',
            'header' => 'Failed conversion',
            'description' => 'CSV has error in conversion',
        ],
        [
            'slug' => 'unsyncable',
            'header' => 'Unsyncable',
            'description' => 'JSON files cannot be synced to CDIS',
        ],
        [
            'slug' => 'invalid_files',
            'header' => 'Invalid files',
            'description' => 'CSV files which are invalid',
        ],
    ];
}
