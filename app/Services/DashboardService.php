<?php

namespace App\Services;

use App\Repositories\Contracts\FileStorageSetupRepository;
use App\Traits\StorageTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DashboardService
{
    use StorageTrait;
    public function getSummary($filters)
    {
        $summary = [];

        $entry = 'transaction';
        $fileStorageSetup = app()->make(FileStorageSetupRepository::class)->where('name', 'POS TO CDIS (DEFAULT)')->first();

        $selectedDisk = $this->intializeDisk($fileStorageSetup, \App\Enums\StorageCommandSelection::RESEND);

        if (isset($selectedDisk) && is_array($selectedDisk)) {
            $remoteDiskName = $selectedDisk['remoteDiskName'];
            $localDiskName = $selectedDisk['localDiskName'];
        } else {
            return false;
        }

        $entryFolderName = Str::title(str_replace('_', ' ', $entry));

        $localDisk = Storage::disk($localDiskName);
        $remoteDisk = Storage::disk($remoteDiskName);

        $toSyncPath = "{$entryFolderName}/Converted/To sync";
        $syncedPath =  "{$entryFolderName}/Converted/Synced";
        $resyncPath =  "{$entryFolderName}/Converted/Failed sync/Resync";
        $unsyncablePath =  "{$entryFolderName}/Converted/Failed sync/Unsyncable";

        $toFetchPath =  "{$entryFolderName}/To fetch";
        $toConvertPath =  "{$entryFolderName}/To convert";
        $failedConversionPath =  "{$entryFolderName}/Failed conversion";
        $failedConversionErrorsPath =  "{$entryFolderName}/Failed conversion/Errors";
        $invalidFilesPath =  "{$entryFolderName}/Failed conversion/Invalid files";

        try {

            $this->createDirectoryIfNotExist($localDisk, $toSyncPath);
            $this->createDirectoryIfNotExist($localDisk, $syncedPath);
            $this->createDirectoryIfNotExist($localDisk, $resyncPath);
            $this->createDirectoryIfNotExist($localDisk, $unsyncablePath);
            $this->createDirectoryIfNotExist($localDisk, $toConvertPath);
            $this->createDirectoryIfNotExist($localDisk, $failedConversionPath);
            $this->createDirectoryIfNotExist($localDisk, $invalidFilesPath);
        } catch (\Exception $ex) {
        } finally {
            $toConvertDirectories = $localDisk->directories($toConvertPath);
            $toSyncFiles = $localDisk->files($toSyncPath);
            $unsycableFiles = $localDisk->files($unsyncablePath);
            $failedConversionDirectories = $localDisk->directories($failedConversionPath);
            $invalidFilesDirectories = $localDisk->directories($invalidFilesPath);
            $toFetchDirectories = $remoteDisk->directories($toFetchPath);

            $exceptDirectories = [
                $failedConversionErrorsPath,
                $invalidFilesPath
            ];
            $filteredFaileConversion = collect($failedConversionDirectories)->filter(function ($value, $key) use ($exceptDirectories) {
                return !in_array($value, $exceptDirectories);
            });

            $summary = [
                'to_convert' => count($toConvertDirectories),
                'to_sync' => count($toSyncFiles),
                'to_fetch' => count($toFetchDirectories),
                'failed_conversion' => count($filteredFaileConversion),
                'unsyncable' => count($unsycableFiles),
                'invalid_files' => count($invalidFilesDirectories),
            ];
        }

        return (object) $summary;
    }
}
