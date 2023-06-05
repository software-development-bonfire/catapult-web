<?php

namespace App\Console\Commands\POSToCDIS;

use App\Entities\Configuration;
use App\Enums\CatapultSyncStatus;
use App\Enums\Status;
use App\Enums\StorageType;
use App\Helpers\CustomPinger as Ping;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Services\ErrorLogService;
use App\Traits\ConsoleCommandTrait;
use App\Traits\GenericHelper;
use App\Traits\OutputBufferTrait;
use App\Traits\PusherTrait;
use App\Traits\StorageTrait;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HardResync extends Command implements ShouldQueue
{
    use GenericHelper, OutputBufferTrait, StorageTrait, PusherTrait, ConsoleCommandTrait;

    public $errorLogService;

    private $moveAllFiles = true;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:hard-resync {--type=HARD_RESYNC}{--user_bid=null}';

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
        $timeStart = microtime(true);

        $this->initializePusher();
        ini_set('max_execution_time', '-1');
        ini_set('memory_limit', '-1');

        $catapultActionType = $this->option('type');
        $userBid = $this->option('user_bid');

        $branchCode = config('configuration.branch_code');
        $cdisUrl = getDomain(config()->get('app.cdis_url'), true);
        $cdisDomainName = getDomain($cdisUrl, false);

        $this->call('network:resolve');

        $entries = [
            'transaction',
            'zread',
            'audit_trail',
            'cash_breakdown',
            'cash_drawer',
        ];

        $unsyncableFilesCountMoved = 0;
        $entriesMaxLength = max(array_map('strlen', $entries));

        foreach ($entries as $entry) {
            $entryLogLabel = $this->computedLogLabel($entriesMaxLength, $entry);

            $remoteDiskName = '';
            $localDiskName = '';

            $filters = (object) [
                'data_entry' => $entry,
                'status' => Status::ACTIVE,
            ];

            $fieldMappingDetails = Cache::remember("file_storage_setup_{$entry}", 60 * 60, function () use ($filters) {
                return app()
                    ->make(FieldMappingRepository::class)
                    ->list($filters, false, ['fileStorageSetup']);
            });

            if (count($fieldMappingDetails) > 0) {
                $fieldMappingDetails = $fieldMappingDetails[0];

                $fileStorageSetup = $fieldMappingDetails->fileStorageSetup;

                $selectedDisk = $this->intializeDisk($fileStorageSetup, \App\Enums\StorageCommandSelection::RESEND);

                if (isset($selectedDisk) && is_array($selectedDisk)) {
                    $remoteDiskName = $selectedDisk['remoteDiskName'];
                    $localDiskName = $selectedDisk['localDiskName'];
                } else {
                    return false;
                }

                $entryFolderName = Str::title(str_replace('_', ' ', $entry));

                $localDisk = Storage::disk($localDiskName);

                $toSyncPath = "{$entryFolderName}/Converted/To sync";
                $syncedPath = "{$entryFolderName}/Converted/Synced";
                $resyncPath = "{$entryFolderName}/Converted/Failed sync/Resync";
                $unsyncablePath = "{$entryFolderName}/Converted/Failed sync/Unsyncable";

                $toConvertPath = "{$entryFolderName}/To convert";
                $failedConversionFolderPathErrors = "{$entryFolderName}/Failed conversion/Errors";

                try {

                    $this->createDirectoryIfNotExist($localDisk, $toSyncPath);
                    $this->createDirectoryIfNotExist($localDisk, $syncedPath);
                    $this->createDirectoryIfNotExist($localDisk, $resyncPath);
                    $this->createDirectoryIfNotExist($localDisk, $unsyncablePath);
                    $this->createDirectoryIfNotExist($localDisk, $toConvertPath);
                    $this->createDirectoryIfNotExist($localDisk, $failedConversionFolderPathErrors);
                } catch (\Exception $ex) {
                    $this->createLog(__('error.remote_directory_not_exists'), 'error', true, [$entryLogLabel], [$fileStorageSetup->remote_path]);
                    continue;
                }

                $toSyncTargetPath = "/{$toSyncPath}";
                $failedSyncUnsyncablePath = "/{$unsyncablePath}";

                $unsyncableFiles = [];
                if ($this->moveAllFiles) {
                    $unsyncableFiles = $localDisk->files($failedSyncUnsyncablePath);
                } else {
                    // Get files inside Unsyncable folder more than 1 day
                    $files = $localDisk->files($unsyncablePath);
                    $unsyncableFiles = array_filter($files, function ($localDisk, $file) {
                        return $localDisk->lastModified($file) < now()->subDays(1);
                    });
                }

                $unsyncableFilesCount = count($unsyncableFiles);
                if ($unsyncableFilesCount > 0) {
                    $this->createLog(__('info.moving_failed_files_to', ['count' => $unsyncableFilesCount]), 'line', true, [$entryLogLabel], [$toSyncTargetPath]);
                    $progressbar = $this->output->createProgressBar($unsyncableFilesCount);
                    $progressbar->start();
                    foreach ($unsyncableFiles as $file) {
                        $filename = substr($file, strrpos($file, '/') + 1);
                        $targetFilename = "{$toSyncTargetPath}/{$filename}";

                        $localDisk->put($targetFilename, $localDisk->get($file));

                        if ($localDisk->exists($targetFilename)) {
                            $localDisk->delete($file);
                        }

                        $progressbar->advance();
                    }
                    $progressbar->finish();
                    $this->line('');

                    $this->createLog(__('info.moved_failed_files_to', ['count' => $unsyncableFilesCount]), 'info', true, [$entryLogLabel], [$toSyncTargetPath]);
                }
                $unsyncableFilesCountMoved += $unsyncableFilesCount;

                // Get all files inside Unsyncable folder
                // and wait until folder has no files
                $toSyncFilesCount = 1;
                while ($toSyncFilesCount > 0) {
                    try {
                        $toSyncFiles = $localDisk->files($toSyncTargetPath);
                        $toSyncFilesCount = count($toSyncFiles);
                        $this->createLog($toSyncFilesCount . ' for to sync', 'info', true, [$entryLogLabel], [$toSyncTargetPath]);
                    } catch (\Exception $ex) {
                        // Ignore any exception occurs
                    }
                    sleep(5);
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

        $timeEnd = microtime(true);
        $executionTime = ($timeEnd - $timeStart);

        $convertMessage = [
            'execution' => $this->secondsToHumanReadableTime($executionTime),
            'moved' => $unsyncableFilesCountMoved,
            'type' => $catapultActionType,
            'userBid' => $userBid,
        ];
        $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), CatapultSyncStatus::Resynced, $convertMessage, null);
    }

    private function setErrorLog($message)
    {
        $this->createLog($message, 'error', true);
        $this->flushOutputBuffer();

        sleep(5);
    }
}
