<?php

namespace App\Traits;

use App\Enums\Status;
use App\Enums\StorageType;
use App\Repositories\Contracts\FileStorageSetupRepository;
use Illuminate\Support\Carbon;

/**
 * Trait FileStorageSetupTrait
 * @package App\Traits
 */
trait FileStorageSetupTrait
{
    /**
     * Get storage disk for file storage setup
     * 
     * @param TerminalFileSetup $terminalFile
     * @return Storage
     */

     private function getLocalStorageDisk($terminalFile, $prefix = 'transfer_')
     {
        $localPath = $this->getLocalStoragePath($terminalFile);
 
         if (! empty($localPath)) {
             return $this->resolveFilesystemDisk($prefix.cleanNonAlphaNumericChars(strtolower($terminalFile->name)), $localPath);
         }
         return false;
     }

     private function getLocalStoragePath($terminalFile)
     {
         $fileStorageSetup = app()->make(FileStorageSetupRepository::class)
             ->where('status', Status::ACTIVE)
             ->where('storage_type', StorageType::LOCAL_NETWORK)
             ->where('name', 'POS TO CDIS (DEFAULT)')->first();
 
         if (! empty($fileStorageSetup)) {
             return $fileStorageSetup->local_path;
         }
         return false;
     }
}
