<?php

use App\Enums\Directory;
use App\Enums\Disk;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

        Disk::DEFAULT_CSV => [
            'driver' => 'local',
            'root' => public_path('Default csv'),
        ],

        Disk::LOCAL_POS_TO_CDIS => [
            'driver' => 'local',
            'root' => storage_path('app/public')
        ],

        Disk::POS_TO_CDIS => [
            'driver' => 'ftp',
            'host' => '',
            'username' => '',
            'password' => '',
            'port' => 21,
            'root' => storage_path('app/public'),
            'timeout' => 60,
        ],

    ],

     /*
    |--------------------------------------------------------------------------
    | File Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of days that the files stays
    | in the defined folder of every Catapult processes. Files
    | stays more than specified number of days will be deleted
    |
    */

    'file_lifetime' => env('FILE_LIFETIME', '7'),
    'file_cleanup' => env('FILE_CLEANUP', true),

];
