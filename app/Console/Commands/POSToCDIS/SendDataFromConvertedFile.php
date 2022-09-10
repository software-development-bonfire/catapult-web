<?php

namespace App\Console\Commands\POSToCDIS;

use App\Entities\ErrorLog;
use App\Entities\ErrorLogDetail;
use App\Enums\ErrorStatus;
use App\Enums\Status;
use App\Enums\StorageType;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Traits\ErrorLogTrait;
use App\Traits\FilenameRetryCounterTrait;
use App\Traits\GenericHelper;
use App\Traits\OutputBufferTrait;
use App\Traits\StorageTrait;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SendDataFromConvertedFile extends Command
{
    use GenericHelper, StorageTrait, ErrorLogTrait, FilenameRetryCounterTrait, OutputBufferTrait;

    public $extension = '.json';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:send-data-from-converted-file {--timeout=5}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send data from converted file';

    private $fileContentErrors = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $timeout = $this->option('timeout');
        $timeout =
            filter_var($timeout, FILTER_VALIDATE_BOOLEAN) && is_bool($timeout)
            ? (float) config('sync.pos.to_cdis.timeout')
            : ((float) $timeout
                ? filter_var($timeout, FILTER_VALIDATE_FLOAT)
                : false
            );

        if ($timeout < 0.3 || (!is_float($timeout))) {
            $this->createLog('Timeout value must be equal or greater than 0.3.', 'error', true, []);

            return;
        }

        $maximumRetry = config('sync.pos.to_cdis.max_retry') ?? 5;

        $entries = [
            'transaction',
            'zread',
            'audit_trail',
            'cash_breakdown',
            'cash_drawer',
        ];

        foreach (array_keys($entries) as $entry) {
            Cache::forget('api_and_file_storage_setup_'.$entry);
        }

        while (true) {
            $entriesMaxLength = max(array_map('strlen', $entries));
            $remoteDiskName = '';
            $localDiskName = '';

            $hasFilesToSync = false;

            foreach ($entries as $entry) {
                $spaces = ($entriesMaxLength - strlen($entry)) / 2;
                $entryLogLabel = str_repeat(' ', ceil($spaces)).$entry.str_repeat(' ', floor($spaces));

                $filters = (object) [
                    'data_entry' => $entry,
                    'status' => Status::ACTIVE,
                ];

                $fieldMappingDetails = Cache::remember('api_and_file_storage_setup_'.$entry, 60*60, function () use($filters) {
                    return app()
                        ->make(FieldMappingRepository::class)
                        ->list($filters, false, ['fileStorageSetup', 'apiSetup']);
                });

                if (count($fieldMappingDetails) > 0) {
                    $fieldMappingDetails = $fieldMappingDetails[0];
                } else {
                    $this->createLog(
                        __('error.no_api_setup_detected'),
                        'error',
                        true,
                        [$entryLogLabel]
                    );
                    continue;
                }

                $fileStorageSetup = $fieldMappingDetails->fileStorageSetup;
                $apiSetup = $fieldMappingDetails->apiSetup;

                $selectedDisk = $this->intializeDisk($fileStorageSetup);

                if (isset($selectedDisk) && is_array($selectedDisk)) {
                    $remoteDiskName = $selectedDisk['remoteDiskName'];
                    $localDiskName = $selectedDisk['localDiskName'];
                } else {
                    $this->createLog(__('error.no_file_storage_setup_detected'), 'error', true, [$entryLogLabel], []);
                    sleep($timeout);
                    continue;
                }

                $entryFolderName = Str::title(str_replace('_', ' ', $entry));

                $localDisk = Storage::disk($localDiskName);
                $sourcePath = $entryFolderName.'/Converted/To sync';
                $syncedPath = $entryFolderName.'/Converted/Synced';
                $failedSyncResyncPath = $entryFolderName.'/Converted/Failed sync/Resync';
                $failedSyncUnsyncablePath = $entryFolderName.'/Converted/Failed sync/Unsyncable';
                $failedSyncUnsyncableErrorsPath = $failedSyncUnsyncablePath.'/Errors';

                $files = $localDisk->allFiles($sourcePath);

                if (! $files) {
                    $this->createLog(__('message.no_data_to_send'), 'info', true, [$entryLogLabel], []);
                    
                    $this->flushOutputBuffer();

                    continue;
                } else {
                    $hasFilesToSync = true;
                }

                foreach ($files as $file) {
                    $fileContent = $localDisk->get($file);
                    $jsonFileContent = (array) json_decode($fileContent);
                    $fileName = substr($file, strrpos($file, '/') + 1);

                    $this->fileContentErrors = [];

                    if ($this->getRetryCount($fileName) > intval($maximumRetry)) {
                        $this->moveToUnsyncableFolder($localDisk, $failedSyncUnsyncablePath, $file, $fileName);
                        continue;
                    }

                    if (! $this->isValidFileAndContent($fileContent, $fileName)) {

                        $this->moveToUnsyncableFolder($localDisk, $failedSyncUnsyncablePath, $file, $fileName);

                        foreach($this->fileContentErrors as $error) {
                            $this->setErrorLog($entryLogLabel, $fileName, $failedSyncUnsyncablePath, ErrorStatus::SYNCING_ERROR, null, 'Invalid Content', $error);
                        }

                        continue;
                    }

                    $responseBodyContent = null;

                    try {
                        $response = $this->send($jsonFileContent, $apiSetup);
                        $statusCodeLabel = 'Status code: '.$response->getStatusCode();

                        $responseBodyContent = json_decode($response->getBody()->getContents());
                        $errors = isset($responseBodyContent->errors) ? (array) $responseBodyContent->errors : [];

                        if (isset($responseBodyContent->exception) || isset($responseBodyContent->trace)) {
                            $exceptionTrace = $responseBodyContent->exception.': '.$responseBodyContent->message;
                            $this->createLog($exceptionTrace, 'error', true, [$entryLogLabel, $statusCodeLabel], [$file]);
                            $this->setErrorLog($entryLogLabel, $fileName, $failedSyncResyncPath, ErrorStatus::SYNCING_ERROR, null, $statusCodeLabel, $exceptionTrace);

                            $this->moveToUnsyncableFolder($localDisk, $failedSyncUnsyncablePath, $file, $fileName);
                        } else {
                            if ((isset($responseBodyContent->success) && $responseBodyContent->success) || (isset($responseBodyContent->message) && $responseBodyContent->message == 'Duplicate Entry.')) {
                                $this->createLog(
                                    $responseBodyContent->message,
                                    ($responseBodyContent->message == 'Duplicate Entry.' ? 'warn' : 'info'),
                                    true,
                                    [$entryLogLabel, $statusCodeLabel],
                                    [$file]
                                );

                                $this->moveToSyncedFolder($localDisk, $syncedPath, $file, $fileName);
                            } else if (isset($responseBodyContent->success) && !$responseBodyContent->success && count($errors) > 0) {
                                $this->createLog(__('error.failed_to_send_data'), 'error', true, [$entryLogLabel, $statusCodeLabel], [$file]);
                                $this->createLog('    Errors:', 'error', false);
                                foreach ($errors as $error) {
                                    $this->createLog('        -> ' . json_encode($error), 'error', false);
                                    $this->setErrorLog($entryLogLabel, $fileName, $failedSyncResyncPath, ErrorStatus::SYNCING_ERROR, null, $statusCodeLabel, json_encode($error));
                                }
                                $this->moveToResyncFolder($localDisk, $failedSyncResyncPath, $file, $fileName);
                            } else if (isset($responseBodyContent->success) && !$responseBodyContent->success || (isset($responseBodyContent->message) && $responseBodyContent->message == 'Request failed.')) {
                                $this->createLog(__('error.failed_to_send_data'), 'error', true, [$entryLogLabel, $statusCodeLabel], [$file]);
                                $this->createLog('    Cause: '.$responseBodyContent->message, 'error', false);
                              
                                $this->setErrorLog($entryLogLabel, $fileName, $failedSyncResyncPath, ErrorStatus::SYNCING_ERROR, null, $statusCodeLabel, $responseBodyContent->message);
                                $this->moveToResyncFolder($localDisk, $failedSyncResyncPath, $file, $fileName);
                            } else {
                                $this->createLog(__('error.failed_to_send_data'), 'error', true, [$entryLogLabel, $statusCodeLabel], [$file]);

                                $this->setErrorLog($entryLogLabel, $fileName, $failedSyncResyncPath, ErrorStatus::SYNCING_ERROR, null, $statusCodeLabel, $responseBodyContent->message);
                                $this->moveToResyncFolder($localDisk, $failedSyncResyncPath, $file, $fileName);
                            }
                        }
                    } catch (\Exception $exception) {
                        $this->createLog($exception->getMessage(), 'warn', true, [$entryLogLabel]);

                        $this->setErrorLog($entryLogLabel, $fileName, $failedSyncResyncPath, ErrorStatus::SYNCING_ERROR, null, 'Exception', $exception->getMessage());
                        $this->moveToResyncFolder($localDisk, $failedSyncResyncPath, $file, $fileName);

                        sleep($timeout);

                        $this->flushOutputBuffer();

                        continue;
                    }
                }

                $this->createErrorLogFile($localDisk, $failedSyncUnsyncableErrorsPath, ErrorStatus::SYNCING_ERROR);
            }

            $this->flushOutputBuffer();

            if (! $hasFilesToSync) {
                sleep($timeout);
            }
        }
    }

    public function send($fileContent, $apiSetup)
    {
        $client = new Client([
            'verify' => false,
            'http_errors' => false,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]);

        $senderDetails = ['sender_details' => $this->getSenderDetails()];
        $content = array_merge($senderDetails, $fileContent);

        $options = [
            'json' => $content,
            'headers' => [
                'Accept' => 'application/json',
            ]
        ];

        $response = $client->request('POST', $apiSetup->end_point, $options);

        return $response;
    }

    private function getSenderDetails()
    {
        return [
            'client_id' => config('configuration.client_id'),
            'product_key' => config('configuration.product_key'),
            'branch_code' => config('configuration.branch_code'),
            'system_datetime' => Carbon::now()->format('Y-m-d h:i:s'),
        ];
    }

    private function isValidFileAndContent($jsonContent, $filename)
    {
        if (! $this->hasValidFilenamePattern($filename)) {
            $this->fileContentErrors[] = __('message.file_has_invalid_pattern');
            return false;
        }

        if (! isJsonExtension($filename)) {
            $this->fileContentErrors[] = __('message.file_is_not_json');
            return false;
        }

        if (empty($jsonContent)) {
            $this->fileContentErrors[] = __('message.empty_json_file');
            return false;
        }

        if (!isValidJson($jsonContent)) {
            $this->fileContentErrors[] = __('message.not_valid_json_file');
            return false;
        }

        $json = json_decode($jsonContent, true);
        if (isset($json['transaction'])) {
            $transaction = $json['transaction'][0];

            if (!isset($transaction['transaction_id'])) {
                $this->fileContentErrors[] = __('message.key_not_present', ['key' => 'transaction_id']);
            }

            if (isset($transaction['official_receipt'])) {
                $officialReceipt = $transaction['official_receipt'][0];

                if (!isset($officialReceipt['or_number'])) {
                    $this->fileContentErrors[] = __('message.key_not_present', ['key' => 'or_number']);
                }
            } else {
                $this->fileContentErrors[] = __('message.key_not_present', ['key' => 'official_receipt']);
            }
        } else {
            $this->fileContentErrors[] = __('message.key_not_present', ['key' => 'transaction']);
        }

        if (count($this->fileContentErrors) > 0) {
            return false;
        }

        return true;
    }

    private function moveToSyncedFolder($localDisk, $destinationFolder, $file, $fileName)
    {
        $targetFile = $destinationFolder.'/'.$this->removeRetryCount($fileName);
        $this->moveFile($localDisk, $file, $targetFile);
    }

    private function moveToUnsyncableFolder($localDisk, $destinationFolder, $file, $fileName)
    {
        $targetFile = $destinationFolder.'/'.$this->removeRetryCount($fileName);
        $this->moveFile($localDisk, $file, $targetFile);
    }

    private function moveToResyncFolder($localDisk, $destinationFolder, $file, $fileName)
    {
        $targetFile = $destinationFolder.'/'.$this->setRetryCount($fileName);
        $this->moveFile($localDisk, $file, $targetFile);
    }
}
