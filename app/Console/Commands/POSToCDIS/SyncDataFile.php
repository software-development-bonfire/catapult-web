<?php

namespace App\Console\Commands\POSToCDIS;

use App\Entities\Configuration;
use App\Entities\fileStorageSetup;
use App\Enums\Status;
use App\Enums\StorageType;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Services\ErrorLogService;
use App\Traits\GenericHelper;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncDataFile extends Command implements ShouldQueue
{
    use GenericHelper;

    public $errorLogService;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:sync-data-file';

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

        while (true) {
            $entries = [
                'transaction',
                'zread',
                'audit_trail',
                'cash_breakdown',
                'cash_drawer',
            ];

            $entriesMaxLength = max(array_map('strlen', $entries));

            foreach ($entries as $entry) {
                $spaces = ($entriesMaxLength - strlen($entry)) / 2;
                $entryLogLabel = str_repeat(' ', ceil($spaces)).$entry.str_repeat(' ', floor($spaces));

                $remoteDiskName = '';
                $localDiskName = '';

                $filters = (object) [
                    'data_entry' => $entry,
                    'status' => Status::ACTIVE,
                ];

                $fieldMappingDetails = app()
                    ->make(FieldMappingRepository::class)
                    ->list($filters, false, ['fileStorageSetup']);

                if (count($fieldMappingDetails) > 0) {
                    $fieldMappingDetails = $fieldMappingDetails[0];
                } else {
                    $this->createLog(
                        __('error.no_field_mapping_detected'),
                        'error',
                        true,
                        [$entryLogLabel]
                    );
                    continue;
                }

                $fileStorageSetup = $fieldMappingDetails->fileStorageSetup;

                if ($fileStorageSetup->storage_type == StorageType::FTP) {
                    $remoteDiskName = 'pos_ftp_remote_sync_data_file';
                    $localDiskName = 'pos_ftp_local_sync_data_file';

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
                    $remoteDiskName = 'pos_local_remote_sync_data_file';
                    $localDiskName = 'pos_local_local_sync_data_file';

                    resolve('filesystem')->forgetDisk($remoteDiskName);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'local');
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $fileStorageSetup->remote_path);

                    resolve('filesystem')->forgetDisk($localDiskName);
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $fileStorageSetup->local_path);
                } else {
                    return false;
                }

                $entryFolderName = Str::title(str_replace('_', ' ', $entry));

                $localDisk = Storage::disk($localDiskName);

                try {
                    $remoteDisk = Storage::disk($remoteDiskName);

                    if (! $remoteDisk->exists($entryFolderName.'/To fetch')) {
                        $remoteDisk->makeDirectory($entryFolderName.'/To fetch');
                    }

                    if (! $remoteDisk->exists($entryFolderName.'/Fetched')) {
                        $remoteDisk->makeDirectory($entryFolderName.'/Fetched');
                    }
                } catch (\Exception $ex) {
                    $this->createLog(__('error.remote_directory_not_exists'), 'error', true, [$entryLogLabel], [$fileStorageSetup->remote_path]);
                    continue;
                }

                $remoteSourcePath = '/'.$entryFolderName.'/To fetch';
                $remoteFetchedFolder = '/'.$entryFolderName.'/Fetched';
                $directories = $remoteDisk->allDirectories($remoteSourcePath);


                if (! $directories) {
                    $this->createLog(__('message.no_data_to_sync'), 'info', true, [$entryLogLabel]);

                    continue;
                }

                foreach ($directories as $directory) {
                    $fileCount = substr($directory, -1);
                    $folderName = substr($directory, strrpos($directory, '/') + 1);

                    $files = $remoteDisk->files($directory);

                    if (count($files) == $fileCount) {
                        $this->createLog(__('label.syncing').' :', 'info', true, [$entryLogLabel], [$directory]);

                        foreach ($files as $file) {
                            $filename = substr($file, strrpos($file, '/') + 1);
                            $localDisk->put($entryFolderName.'/To Convert/'.$folderName.'/'.$filename, $remoteDisk->get($file));
                        }

                        if ($remoteDisk->exists($remoteFetchedFolder.'/'.$folderName)) {
                            $remoteDisk->deleteDirectory($remoteSourcePath.'/'.$folderName);
                        } else {
                            $remoteDisk->move($remoteSourcePath.'/'.$folderName, $remoteFetchedFolder.'/'.$folderName);
                        }

                        $this->createLog(__('label.synced').'  :', 'info', true, [$entryLogLabel], [$directory]);
                    }
                }
            }

            sleep(5);
        }
    }
}
