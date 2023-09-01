<?php

namespace App\Traits;

use App\Enums\StorageCommandSelection;
use App\Enums\StorageType;
use Illuminate\Support\Facades\Storage;

/**
 * Trait StorageTrait
 * @package App\Traits
 */
trait StorageTrait
{
    /**
     * Get disk depending on storage type
     *
     * @param object  $fileStorageSetup
     *
     * @return FileSystem
     */
    public function intializeDisk($fileStorageSetup , $storageCommandSelection = StorageCommandSelection::SYNC)
    {
        $remoteDiskName = null;
        $localDiskName = null;

        if ($fileStorageSetup->storage_type == StorageType::FTP) {
            $selectedDisk = $this->fileSystemFTPDiskSelection($storageCommandSelection);
            $remoteDiskName = $selectedDisk['remoteDiskName'];
            $localDiskName = $selectedDisk['localDiskName'];

            resolve('filesystem')->forgetDisk($remoteDiskName);
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'ftp');
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.host', $fileStorageSetup->host);
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.username', $fileStorageSetup->username);
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.password', $fileStorageSetup->password);
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.port', $fileStorageSetup->port);
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $fileStorageSetup->remote_path);

            resolve('filesystem')->forgetDisk($localDiskName);
            app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
            app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $fileStorageSetup->local_path);
        } else if ($fileStorageSetup->storage_type == StorageType::LOCAL_NETWORK) {

            $selectedDisk = $this->fileSystemLocalDiskSelection($storageCommandSelection);
            $remoteDiskName = $selectedDisk['remoteDiskName'];
            $localDiskName = $selectedDisk['localDiskName'];

            resolve('filesystem')->forgetDisk($remoteDiskName);
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'local');
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $fileStorageSetup->remote_path);

            resolve('filesystem')->forgetDisk($localDiskName);
            app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
            app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $fileStorageSetup->local_path);
        } else {
        }

        return [
            'remoteDiskName' => $remoteDiskName,
            'localDiskName' => $localDiskName
        ];
    }

    private function fileSystemLocalDiskSelection($storageCommandSelection = StorageCommandSelection::RESEND)
    {
        $remoteDiskName = null;
        $localDiskName = null;

        if ($storageCommandSelection === StorageCommandSelection::CONVERT) {
            $remoteDiskName = 'pos_local_remote_convert_data_file';
            $localDiskName = 'pos_local_local_convert_data_file';
        } else if ($storageCommandSelection === StorageCommandSelection::SEND) {
            $remoteDiskName = 'pos_local_remote_send_data_from_converted_file';
            $localDiskName = 'pos_local_local_send_data_from_converted_file';
        } else if ($storageCommandSelection === StorageCommandSelection::SYNC) {
            $remoteDiskName = 'pos_local_remote_sync_data_file';
            $localDiskName = 'pos_local_local_sync_data_file';
        } else if ($storageCommandSelection === StorageCommandSelection::RESEND) {
            $remoteDiskName = 'pos_local_remote_resend_file';
            $localDiskName = 'pos_local_local_resend_file';
        } else {
            $remoteDiskName = 'cdis_local_remote_convert_data_to_file';
            $localDiskName = 'cdis_local_remote_convert_data_to_file';
        }

        return [
            'remoteDiskName' => $remoteDiskName,
            'localDiskName' => $localDiskName
        ];
    }

    private function fileSystemFTPDiskSelection($storageCommandSelection = StorageCommandSelection::RESEND)
    {
        $remoteDiskName = null;
        $localDiskName = null;

        if ($storageCommandSelection === StorageCommandSelection::CONVERT) {
            $remoteDiskName = 'pos_ftp_remote_convert_data_file';
            $localDiskName = 'pos_ftp_local_convert_data_file';
        } else if ($storageCommandSelection === StorageCommandSelection::SEND) {
            $remoteDiskName = 'pos_ftp_remote_send_data_from_converted_file';
            $localDiskName = 'pos_ftp_local_send_data_from_converted_file';
        } else if ($storageCommandSelection === StorageCommandSelection::SYNC) {
            $remoteDiskName = 'pos_ftp_remote_sync_data_file';
            $localDiskName = 'pos_ftp_local_sync_data_file';
        } else if ($storageCommandSelection === StorageCommandSelection::RESEND) {
            $remoteDiskName = 'pos_ftp_remote_resend_file';
            $localDiskName = 'pos_ftp_local_resend_file';
        } else {
            $remoteDiskName = 'cdis_ftp_remote_convert_data_to_file';
            $localDiskName = 'cdis_ftp_remote_convert_data_to_file';
        }

        return [
            'remoteDiskName' => $remoteDiskName,
            'localDiskName' => $localDiskName
        ];
    }

     /**
     * Resolve filesytem disk dynamically
     *
     * @param string  $diskName
     * @param string  $defaultPath
     *
     * @return Illuminate\Support\Facades\Storage
     */
    public function resolveFilesystemDisk($diskName, $defaultPath)
    {
        resolve('filesystem')->forgetDisk($diskName);
        app()['config']->set("filesystems.disks.$diskName.driver", 'local');
        app()['config']->set("filesystems.disks.$diskName.root", $defaultPath);

        return Storage::disk($diskName);
    }

    /**
     * Check directory inside root folder and create if not exist
     *
     * @param string  $rootFolder
     * @param string  $targetFoler
     *
     * @return FileSystem
     */
    public function checkDirectory($rootFolder, $targetFoler)
    {
        $checked = true;
        try {
            $remoteDisk = Storage::disk($rootFolder);
            if (! $remoteDisk->exists($targetFoler)) {
                $remoteDisk->makeDirectory($targetFoler);
            }
        } catch (\Exception $ex) {
            $checked = false;
        }
        return $checked;
    }

    /**
     * Create directory if not exist
     *
     * @param FileSystem  $disk
     * @param string  $directory
     */
    public function createDirectoryIfNotExist($disk, $directory)
    {
        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }
    }

    /**
     * Move file
     *
     * @param FileSystem  $disk
     * @param string  $sourceFile
     * @param string  $targetFile
     */
    public function moveFile($disk, $sourceFile, $targetFile)
    {
        if ($disk->exists($targetFile)) {
            $disk->delete($targetFile);
        }
        $disk->move($sourceFile, $targetFile);
    }

    /**
     * Copy file
     *
     * @param FileSystem  $disk
     * @param string  $sourceFile
     * @param string  $targetFile
     */
    public function copyFile($disk, $sourceFile, $targetFile)
    {
        if ($disk->exists($targetFile)) {
            $disk->delete($targetFile);
        }
        $disk->copy($sourceFile, $targetFile);
    }

    /**
     * Delete files with specified number of days
     *
     * @param FileSystem  $localDisk
     * @param string  $directory
     */
    public function cleanupFiles($localDisk, $directory)
    {
        $filesCount = 0;
        try {
            $fileLifetime = config('filesystems.file_lifetime');
            // Get files inside the folder more than specified days
            $files = $localDisk->files($directory);
            foreach ($files as $file) {
                if ($localDisk->lastModified($file) < now()->subDays($fileLifetime)->getTimestamp()) {
                    try {
                        $localDisk->delete($file);
                    } catch (\Exception $ex) {
                    }
                    $filesCount += 1;
                }
            }
        } catch (\Exception $ex) {
            return false;
        }
        return $filesCount;
    }

    /**
     * Delete directories with specified number of days
     *
     * @param FileSystem  $localDisk
     * @param string  $directory
     */
    public function cleanupDirectories($localDisk, $directory)
    {
        $directoryCount = 0;
        try {
            $fileLifetime = config('filesystems.file_lifetime');
            // Get directories inside the folder more than specified days
            $directories = $localDisk->allDirectories($directory);
            foreach ($directories as $directory) {
                if ($localDisk->lastModified($directory) < now()->subDays($fileLifetime)->getTimestamp()) {
                    try {
                        $localDisk->deleteDirectory($directory);
                    } catch (\Exception $ex) {
                        //If errors occured due to directory is not empty, then
                        // We need to clear all files first before deleting the directory
                        if ($this->cleanupFiles($localDisk, $directory)) {
                            $localDisk->deleteDirectory($directory);
                        }
                    }
                    $directoryCount += 1;
                }
            }
        } catch (\Exception $ex) {
            return false;
        }
        return $directoryCount;
    }
}
