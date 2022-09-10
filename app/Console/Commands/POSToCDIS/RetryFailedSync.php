<?php

namespace App\Console\Commands\POSToCDIS;

use App\Entities\Configuration;
use App\Enums\Status;
use App\Enums\StorageType;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Services\ErrorLogService;
use App\Traits\GenericHelper;
use App\Traits\OutputBufferTrait;
use App\Traits\StorageTrait;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RetryFailedSync extends Command implements ShouldQueue
{
    use GenericHelper, OutputBufferTrait, StorageTrait;

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
        $this->line(__('info.watching_files_to_resync'));
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

                $fieldMappingDetails = Cache::remember('file_storage_setup_'.$entry, 60 * 60, function () use ($filters) {
                    return app()
                        ->make(FieldMappingRepository::class)
                        ->list($filters, false, ['fileStorageSetup']);
                });

                if (count($fieldMappingDetails) > 0) {
                    $fieldMappingDetails = $fieldMappingDetails[0];

                    $fileStorageSetup = $fieldMappingDetails->fileStorageSetup;

                    $selectedDisk = $this->intializeDisk($fileStorageSetup);

                    if (isset($selectedDisk) && is_array($selectedDisk)) {
                        $remoteDiskName = $selectedDisk['remoteDiskName'];
                        $localDiskName = $selectedDisk['localDiskName'];
                    } else {
                        return false;
                    }

                    $entryFolderName = Str::title(str_replace('_', ' ', $entry));

                    $localDisk = Storage::disk($localDiskName);

                    $toSyncPath = $entryFolderName.'/Converted/To sync';
                    $syncedPath = $entryFolderName.'/Converted/Synced';
                    $resyncPath = $entryFolderName.'/Converted/Failed sync/Resync';
                    $unsyncablePath = $entryFolderName.'/Converted/Failed sync/Unsyncable';

                    try {

                        $this->createDirectoryIfNotExist($localDisk, $toSyncPath);
                        $this->createDirectoryIfNotExist($localDisk, $syncedPath);
                        $this->createDirectoryIfNotExist($localDisk, $resyncPath);
                        $this->createDirectoryIfNotExist($localDisk, $unsyncablePath);
                        
                    } catch (\Exception $ex) {
                        $this->createLog(__('error.remote_directory_not_exists'), 'error', true, [$entryLogLabel], [$fileStorageSetup->remote_path]);
                        continue;
                    }

                    if (!$this->hasInternetConnection()) {
                        $this->createLog(__('message.no_internet_connection'), 'error', true,  [$entryLogLabel]);

                        $this->flushOutputBuffer();
                        continue;
                    }

                    $toSyncTargetPath = '/'.$toSyncPath;
                    $failedSyncResyncPath = '/'.$resyncPath;

                    $toResyncFiles = $localDisk->files($failedSyncResyncPath);
                    $toResyncFilesCount = count($toResyncFiles);

                    if ($toResyncFilesCount > 0) {
                        $this->createLog(__('message.moving_failed_files_to', ['count' => $toResyncFilesCount]), 'line', true, [$entryLogLabel], [$toSyncTargetPath]);
                        $progressbar = $this->output->createProgressBar($toResyncFilesCount);
                        $progressbar->start();
                        foreach ($toResyncFiles as $file) {
                            $filename = substr($file, strrpos($file, '/') + 1);
                            $targetFilename = $toSyncTargetPath.'/'.$filename;

                            $localDisk->put($targetFilename, $localDisk->get($file));
                            
                            if ($localDisk->exists($targetFilename)) {
                                $localDisk->delete($file);
                            }
                            
                            $progressbar->advance();
                        }
                        $progressbar->finish();
                        $this->line('');

                        $this->createLog(__('message.moved_failed_files_to', ['count' => $toResyncFilesCount]), 'info', true, [$entryLogLabel], [$toSyncTargetPath]);
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
            
            $this->flushOutputBuffer();
            sleep(5);
        }
    }
}
