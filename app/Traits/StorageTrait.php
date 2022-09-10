<?php

namespace App\Traits;

use App\Enums\StorageType;
use Illuminate\Support\Facades\Storage;

trait StorageTrait
{
    public function intializeDisk($fileStorageSetup)
    {
        $remoteDiskName = null;
        $localDiskName = null;

        if ($fileStorageSetup->storage_type == StorageType::FTP) {
            $remoteDiskName = 'pos_ftp_remote_send_data_from_converted_file';
            $localDiskName = 'pos_ftp_local_send_data_from_converted_file';

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
            $remoteDiskName = 'pos_local_remote_send_data_from_converted_file';
            $localDiskName = 'pos_local_local_send_data_from_converted_file';

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

    public function checkDirectory($rootFolder, $targetFoler)
    {
        $checked = true;
        try {
            $remoteDisk = Storage::disk($rootFolder);
            if (!$remoteDisk->exists($targetFoler)) {
                $remoteDisk->makeDirectory($targetFoler);
            }
        } catch (\Exception $ex) {
            $checked = false;
        }
        return $checked;
    }

    public function createDirectoryIfNotExist($disk, $directory)
    {
        if (!$disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }
    }

    public function moveFile($disk, $sourceFile, $targetFile)
    {
        if ($disk->exists($targetFile)) {
            $disk->delete($targetFile);
        }
        $disk->move($sourceFile, $targetFile);
    }
}
