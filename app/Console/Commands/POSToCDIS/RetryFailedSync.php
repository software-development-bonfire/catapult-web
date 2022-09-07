<?php

namespace App\Console\Commands\POSToCDIS;

use App\Entities\Configuration;
use App\Entities\fileStorageSetup;
use App\Enums\Status;
use App\Enums\StorageType;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Services\ErrorLogService;
use App\Traits\GenericHelper;
use Defuse\Crypto\File as CryptoFile;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use File;
use Illuminate\Http\File as HttpFile;
use Illuminate\Support\Facades\File as FacadesFile;

class RetryFailedSync extends Command implements ShouldQueue
{
    use GenericHelper;

    public $errorLogService;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:failed-sync-retry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data files from POS FTP or Local Folder to Catapult Local';

    /**
     * Create a new command instance.
     *
     * @param ErrorLogService  $errorLogService
     * @return void
     */
    public function __construct(ErrorLogService $errorLogService)
    {
        parent::__construct();

        $this->errorLogService = $errorLogService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('Syncing started..');
        $this->line('');

        $entryLimit = Configuration::where('attribute', 'pos_to_cdis_entry_limit')->first();

        $entries = [
            'transaction',
            'zread',
            'audit_trail',
            'cash_breakdown',
            'cash_drawer',
        ];

        foreach (array_keys($entries) as $entry) {
            Cache::forget('file_storage_setup_' . $entry);
        }

        while (true) {
            $entriesMaxLength = max(array_map('strlen', $entries));

            foreach ($entries as $entry) {
                $spaces = ($entriesMaxLength - strlen($entry)) / 2;
                $entryLogLabel = str_repeat(' ', ceil($spaces)) . $entry . str_repeat(' ', floor($spaces));

                $remoteDiskName = '';
                $localDiskName = '';

                $filters = (object) [
                    'data_entry' => $entry,
                    'status' => Status::ACTIVE,
                ];

                $fieldMappingDetails = Cache::remember('file_storage_setup_' . $entry, 60 * 60, function () use ($filters) {
                    return app()
                        ->make(FieldMappingRepository::class)
                        ->list($filters, false, ['fileStorageSetup']);
                });

                if (count($fieldMappingDetails) > 0) {
                    $fieldMappingDetails = $fieldMappingDetails[0];

                    $fileStorageSetup = $fieldMappingDetails->fileStorageSetup;

                    if ($fileStorageSetup->storage_type == StorageType::FTP) {
                        $remoteDiskName = 'pos_ftp_remote_sync_data_file';
                        $localDiskName = 'pos_ftp_local_sync_data_file';

                        resolve('filesystem')->forgetDisk($remoteDiskName);
                        app()['config']->set('filesystems.disks.' . $remoteDiskName . '.driver', 'ftp');
                        app()['config']->set('filesystems.disks.' . $remoteDiskName . '.host', $fileStorageSetup->host);
                        app()['config']->set('filesystems.disks.' . $remoteDiskName . '.username', $fileStorageSetup->username);
                        app()['config']->set('filesystems.disks.' . $remoteDiskName . '.password', $fileStorageSetup->password);
                        app()['config']->set('filesystems.disks.' . $remoteDiskName . '.port', $fileStorageSetup->port);
                        app()['config']->set('filesystems.disks.' . $remoteDiskName . '.root', $fileStorageSetup->remote_path);

                        resolve('filesystem')->forgetDisk($localDiskName);
                        app()['config']->set('filesystems.disks.' . $localDiskName . '.driver', 'local');
                        app()['config']->set('filesystems.disks.' . $localDiskName . '.root', $fileStorageSetup->local_path);
                    } else if ($fileStorageSetup->storage_type == StorageType::LOCAL_NETWORK) {
                        $remoteDiskName = 'pos_local_remote_sync_data_file';
                        $localDiskName = 'pos_local_local_sync_data_file';

                        resolve('filesystem')->forgetDisk($remoteDiskName);
                        app()['config']->set('filesystems.disks.' . $remoteDiskName . '.driver', 'local');
                        app()['config']->set('filesystems.disks.' . $remoteDiskName . '.root', $fileStorageSetup->remote_path);

                        resolve('filesystem')->forgetDisk($localDiskName);
                        app()['config']->set('filesystems.disks.' . $localDiskName . '.driver', 'local');
                        app()['config']->set('filesystems.disks.' . $localDiskName . '.root', $fileStorageSetup->local_path);
                    } else {
                        return false;
                    }

                    $entryFolderName = Str::title(str_replace('_', ' ', $entry));

                    $localDisk = Storage::disk($localDiskName);

                    try {
                        if (!$localDisk->exists($entryFolderName . '/Converted/To sync')) {
                            $localDisk->makeDirectory($entryFolderName . '/Converted/To sync');
                        }

                        if (!$localDisk->exists($entryFolderName . '/Converted/Synced')) {
                            $localDisk->makeDirectory($entryFolderName . '/Converted/Synced');
                        }

                        if (!$localDisk->exists($entryFolderName . '/Converted/Failed sync/Bad request')) {
                            $localDisk->makeDirectory($entryFolderName . '/Converted/Failed sync/Bad request');
                        }

                        if (!$localDisk->exists($entryFolderName . '/Converted/Failed sync/Unsyncable')) {
                            $localDisk->makeDirectory($entryFolderName . '/Converted/Failed sync/Unsyncable');
                        }
                    } catch (\Exception $ex) {
                        $this->createLog(__('error.remote_directory_not_exists'), 'error', true, [$entryLogLabel], [$fileStorageSetup->remote_path]);
                        continue;
                    }

                    if (!$this->hasInternetConnection()) {
                        $this->createLog('No internet connection', 'error', true,  [$entryLogLabel]);
                        continue;
                    }

                    $toSyncTargetPath = '/' . $entryFolderName . '/Converted/To sync';
                    $syncedTargetPath = '/' . $entryFolderName . '/Converted/Synced';


                    $failedSyncBadRequestPath = '/' . $entryFolderName . '/Converted/Failed sync/Bad request';
                    $failedSyncUnsyncablePath = '/' . $entryFolderName . '/Converted/Failed sync/Unsyncable';

                    $badRequestFiles = $localDisk->files($failedSyncBadRequestPath);
                    $unsyncableFiles = $localDisk->files($failedSyncUnsyncablePath);

                    if (count($badRequestFiles) > 0) {
                        $this->createLog('Moving ' . '[' . count($badRequestFiles) . '] bad request files to ', 'line', true, [$entryLogLabel], [$toSyncTargetPath]);
                        $progressbar = $this->output->createProgressBar(count($badRequestFiles));
                        $progressbar->start();
                        foreach ($badRequestFiles as $file) {
                            $filename = substr($file, strrpos($file, '/') + 1);
                            $localDisk->put($toSyncTargetPath . '/' . $filename, $localDisk->get($file));

                            if ($localDisk->exists($toSyncTargetPath . '/' . $filename)) {
                                $localDisk->delete($file);
                            }
                            $progressbar->advance();
                        }
                        $progressbar->finish();
                        $this->line('');

                        $this->createLog('Moved ' . '[' . count($badRequestFiles) . '] bad request files to ', 'info', true, [$entryLogLabel], [$toSyncTargetPath]);
                    }

                    if (count($unsyncableFiles) > 0) {
                        $this->createLog('Moving ' . '[' . count($unsyncableFiles) . '] unsyncable files to ', 'line', true, [$entryLogLabel], [$toSyncTargetPath]);
                        $progressbar = $this->output->createProgressBar(count($unsyncableFiles));
                        $progressbar->start();
                        foreach ($unsyncableFiles as $file) {
                            $filename = substr($file, strrpos($file, '/') + 1);
                            $localDisk->put($toSyncTargetPath . '/' . $filename, $localDisk->get($file));

                            if ($localDisk->exists($toSyncTargetPath . '/' . $filename)) {
                                $localDisk->delete($file);
                            }
                            $progressbar->advance();
                        }
                        $progressbar->finish();
                        $this->line('');

                        $this->createLog('Moved ' . '[' . count($unsyncableFiles) . '] unsyncable files to ', 'info', true, [$entryLogLabel], [$toSyncTargetPath]);
                    }
                } else {
                    $this->createLog(
                        __('error.no_field_mapping_detected'),
                        'warn',
                        true,
                        [$entryLogLabel]
                    );
                    continue;
                }
            }

            sleep(5);
        }
    }
}
