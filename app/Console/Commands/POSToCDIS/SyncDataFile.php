<?php

namespace App\Console\Commands\POSToCDIS;

use App\Entities\Configuration;
use App\Entities\fileStorageSetup;
use App\Enums\Status;
use App\Enums\StorageType;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Services\ErrorLogService;
use App\Traits\GenericHelper;
use App\Traits\StorageTrait;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncDataFile extends Command implements ShouldQueue
{
    use GenericHelper, StorageTrait;

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

        $entries = [
            'transaction',
            'zread',
            'audit_trail',
            'cash_breakdown',
            'cash_drawer',
        ];

        foreach (array_keys($entries) as $entry) {
            Cache::forget('file_storage_setup_'.$entry);
        }

        while (true) {
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

                $fieldMappingDetails = Cache::remember('file_storage_setup_'.$entry, 60*60, function () use($filters) {
                    return app()
                        ->make(FieldMappingRepository::class)
                        ->list($filters, false, ['fileStorageSetup']);
                });

                if (count($fieldMappingDetails) > 0) {
                    $fieldMappingDetails = $fieldMappingDetails[0];
                } else {
                    $this->createLog(
                        __('error.no_field_mapping_detected'),
                        'warn',
                        true,
                        [$entryLogLabel]
                    );
                    continue;
                }

                $fileStorageSetup = $fieldMappingDetails->fileStorageSetup;

                $selectedDisk = $this->intializeDisk($fileStorageSetup, \App\Enums\StorageCommandSelection::SYNC);

                if (isset($selectedDisk) && is_array($selectedDisk)) {
                    $remoteDiskName = $selectedDisk['remoteDiskName'];
                    $localDiskName = $selectedDisk['localDiskName'];
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

                // Do the cleanup inside Fetched folder
                $this->doCleanup($localDisk, $remoteFetchedFolder, $entryLogLabel);
                $this->doCleanup($remoteDisk, $remoteFetchedFolder, $entryLogLabel);

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

    public function doCleanup($localDisk, $fetchedFolderPath, $entryLogLabel)
    {
        $fileCleanup = config('filesystems.file_cleanup');      
        if ($fileCleanup) {
            $directoryCount = $this->cleanupDirectories($localDisk, $fetchedFolderPath);
            if ($directoryCount) {
                $this->createLog(__('message.directory_cleaned_up', ['value' => $directoryCount]), 'info', true, [$entryLogLabel]);
            }
        }
    }
}
