<?php

namespace App\Console\Commands\POSToCDIS;

use App\Entities\Configuration;
use App\Entities\ErrorLogDetail;
use App\Entities\RemoteSetup;
use App\Entities\SyncFileReference;
use App\Enums\Directory;
use App\Enums\Disk;
use App\Enums\Status;
use App\Enums\StorageType;
use App\Enums\UserType;
use App\Repositories\Contracts\FieldMappingListRepository;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Services\ErrorLogService;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncDataFile extends Command implements ShouldQueue
{
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

            foreach ($entries as $entry) {
                $remoteDiskName = '';
                $localDiskName = '';

                $filters = (object) [
                    'data_entry' => $entry,
                    'status' => Status::ACTIVE,
                ];

                $entryLabel = '('.$entry.') ';

                $fieldMappingDetails = app()
                    ->make(FieldMappingRepository::class)
                    ->list($filters, false, ['remoteSetup']);

                if (count($fieldMappingDetails) > 0) {
                    $fieldMappingDetails = $fieldMappingDetails[0];
                } else {
                    $this->warn('No field mapping details. Please contact administrator. '.$entryLabel);
                    continue;
                }

                $remoteSetup = $fieldMappingDetails->remoteSetup;

                $entryLabel .= '('.StorageType::getDescription($remoteSetup->storage_type).')';

                if ($remoteSetup->storage_type == StorageType::FTP) {
                    $remoteDiskName = 'pos_ftp_remote_sync_data_file';
                    $localDiskName = 'pos_ftp_local_sync_data_file';

                    resolve('filesystem')->forgetDisk($remoteDiskName);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'ftp');
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.host', $remoteSetup->host);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.username', $remoteSetup->username);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.password', $remoteSetup->password);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.port', $remoteSetup->port);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $remoteSetup->remote_path);

                    resolve('filesystem')->forgetDisk($localDiskName);
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $remoteSetup->local_path);
                } else if ($remoteSetup->storage_type == StorageType::LOCAL_NETWORK) {
                    $remoteDiskName = 'pos_local_remote_sync_data_file';
                    $localDiskName = 'pos_local_local_sync_data_file';

                    resolve('filesystem')->forgetDisk($remoteDiskName);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'local');
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $remoteSetup->remote_path);

                    resolve('filesystem')->forgetDisk($localDiskName);
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $remoteSetup->local_path);
                } else {
                    $error = [
                        'endpoint' => 'N/A',
                        'filename' => 'N/A',
                        'status' => Lang::get('error.failed_conversion'),
                        'sheet' => 'N/A',
                        'error_type' => 'Configuration error',
                        'description' => 'No remote setup configuration'
                    ];

                    $errorExist = ErrorLogDetail::where([
                        'error_type' => $error['error_type'],
                        'description' => $error['description']
                    ])->first();

                    if (! $errorExist) {
                        $this->errorLogService->store($error);
                    }
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
                    $this->info('Remote Directory not exists. Please contact administrator '.$entryLabel);
                    continue;
                }

                $remoteSourcePath = '/'.$entryFolderName.'/To fetch';
                $remoteFetchedFolder = '/'.$entryFolderName.'/Fetched';
                $directories = $remoteDisk->allDirectories($remoteSourcePath);

                $this->info(
                    $directories
                        ? 'Sync processing... '.$entryLabel
                        : 'No file to be sync '.$entryLabel);

                foreach ($directories as $directory) {
                    $this->info('Syncing ('.$directory.')');
                    $fileCount = substr($directory, -1);
                    $folderName = substr($directory, strrpos($directory, '/') + 1);

                    $files = $remoteDisk->allFiles($directory);

                    if (count($files) == $fileCount) {
                        foreach ($files as $file) {
                            $filename = substr($file, strrpos($file, '/') + 1);
                            $localDisk->put($entryFolderName.'/To Convert/'.$folderName.'/'.$filename, $remoteDisk->get($file));
                        }

                        if ($remoteDisk->exists($remoteFetchedFolder.'/'.$folderName)) {
                            $remoteDisk->deleteDirectory($remoteSourcePath.'/'.$folderName);
                        } else {
                            $remoteDisk->move($remoteSourcePath.'/'.$folderName, $remoteFetchedFolder.'/'.$folderName);
                        }

                        $this->info(__('message.syncing_file_successful').' '.$entryLabel);
                    }
                }
            }

            sleep(5);
        }
    }
}
