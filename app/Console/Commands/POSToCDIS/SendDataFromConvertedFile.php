<?php

namespace App\Console\Commands\POSToCDIS;

use App\Enums\CDIS\TerminalTransactionType;
use App\Enums\CommonErrors;
use App\Enums\ErrorStatus;
use App\Enums\Status;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Traits\CDISRequestTrait;
use App\Traits\ConsoleCommandTrait;
use App\Traits\ErrorLogTrait;
use App\Traits\FilenameRetryCounterTrait;
use App\Traits\GenericHelper;
use App\Traits\OutputBufferTrait;
use App\Traits\StorageTrait;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SendDataFromConvertedFile extends Command
{
    use GenericHelper, StorageTrait, ErrorLogTrait, FilenameRetryCounterTrait, OutputBufferTrait, CDISRequestTrait, ConsoleCommandTrait;

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
        $cdisUrl = getDomain(config()->get('app.cdis_url'), true);
        $cdisDomainName = getDomain($cdisUrl, false);

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
            if (! $this->hasInternetConnection() && ! $this->hasInternetConnection($cdisUrl)) {
                $this->setErrorLineLog(__('message.no_internet_connection'));
                continue;
            }

            $entriesMaxLength = max(array_map('strlen', $entries));
            $remoteDiskName = '';
            $localDiskName = '';

            $hasFilesToSync = false;

            foreach ($entries as $entry) {
                $entryLogLabel = $this->computedLogLabel($entriesMaxLength, $entry);

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

                $endpointDomain = getDomain($apiSetup->end_point, true);
                if ($endpointDomain !== $cdisUrl) {
                    $this->setErrorLineLog(__('error.configured_endpoint_does_not_matched_to', ['value' => $cdisUrl]));
                    continue;
                }

                $selectedDisk = $this->intializeDisk($fileStorageSetup, \App\Enums\StorageCommandSelection::SEND);

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
                $failedConversionFolderPathErrors = '/'.$entryFolderName.'/Failed conversion/Errors';

                $this->doCleanup($localDisk, $syncedPath, $entryLogLabel);

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
                        $responseBodyContent = json_decode($response->getBody()->getContents());                        
                        $statusCode = $response->getStatusCode();

                        $errors = isset($responseBodyContent->errors) ? (array) $responseBodyContent->errors : [];

                        if ($statusCode >= Response::HTTP_MULTIPLE_CHOICES) {
                            // is HTTP status code (for non-exceptions) 
                            $statusText = Response::$statusTexts[$statusCode];

                            if ($statusCode === Response::HTTP_TOO_MANY_REQUESTS) {
                                // Too many request, we need to extend delay time (10 seconds)
                                // as a rest time after request error
                                sleep(10);
                            }
                            if (
                                $statusCode === Response::HTTP_REQUEST_ENTITY_TOO_LARGE ||
                                $statusCode === Response::HTTP_BAD_REQUEST
                            ) {
                                // File is too large or maybe bad request due to file not found
                                // or extension is not allowed
                                $this->moveToResyncFolder($localDisk, $failedSyncResyncPath, $file, $fileName);
                                sleep(5);
                            }
                            if ($statusCode === Response::HTTP_PRECONDITION_FAILED) {
                                // Due to some missing references, which are a prereq on saving,
                                // we need to add some delay 
                                sleep(10);
                            }
                            $this->setErrorLineLog("{$statusText} : ".json_encode($responseBodyContent),[$statusCode]);
                        } else {
                            if (! empty($responseBodyContent)) {
                                if ($statusCode === Response::HTTP_OK) {
                                    $this->moveToSyncedFolder($localDisk, $syncedPath, $file, $fileName);
                                    $this->createLog(json_encode($responseBodyContent), 'info', true, [Response::$statusTexts[$statusCode]]);
                                } else {
                                    // Display warning message containing API response,
                                    // means request is successfully sent but it does not return
                                    // an expected response
                                    $this->createLog(json_encode($responseBodyContent), 'warn', true, [Response::$statusTexts[$statusCode]]);
                                    $this->setErrorLog($entryLogLabel, $fileName, $failedSyncResyncPath, ErrorStatus::SYNCING_ERROR, null, [Response::$statusTexts[$statusCode]], json_encode($responseBodyContent));
                                }
                            }
                        }
                    } catch (\GuzzleHttp\Exception\TooManyRedirectsException $e) {
                        // handle too many redirects
                        $this->setErrorLineLog(json_encode($e), ['TooManyRedirectsException']);
                        $this->setErrorLog($entryLogLabel, $fileName, $failedSyncResyncPath, ErrorStatus::SYNCING_ERROR, null, 'TooManyRedirectsException', json_encode($e));
                        sleep(10);
                    } catch (\GuzzleHttp\Exception\ClientException | \GuzzleHttp\Exception\ServerException $e) {
                        // ClientException is thrown for 400 level errors if the http_errors request option is set to true.
                        // ServerException is thrown for 500 level errors if the http_errors request option is set to true.
                        if ($e->hasResponse()) {
                            // is HTTP status code, e.g. 500 
                            $statusCode = $e->getResponse()->getStatusCode();
                            if (
                                $statusCode === Response::HTTP_REQUEST_ENTITY_TOO_LARGE ||
                                $statusCode === Response::HTTP_BAD_REQUEST
                            ) {
                                // File is too large or maybe bad request due to file not found
                                // or extension is not allowed
                                $this->moveToResyncFolder($localDisk, $failedSyncResyncPath, $file, $fileName);
                                sleep(5);
                            }
                        }
                        $this->setErrorLineLog(json_encode($e->getResponse()), ['ClientException|ServerException', $statusCode]);
                        $this->setErrorLog($entryLogLabel, $fileName, $failedSyncResyncPath, ErrorStatus::SYNCING_ERROR, null, 'ClientException|ServerException', json_encode($e->getResponse()));
                    } catch (\GuzzleHttp\Exception\ConnectException $e) {
                        // ConnectException is thrown in the event of a networking error.
                        // This may be an error reported by lowlevel functionality 
                        // (e.g.  cURL error)
                        $errno = '';
                        $handlerContext = $e->getHandlerContext();
                        if ($handlerContext['errno'] ?? 0) {
                            // this is the lowlevel error code, not the HTTP status code!!!
                            // for example 6 for "Couldn't resolve host" (for libcurl)
                            $errno = (int)($handlerContext['errno']);
                        }
                        // get a description of the error
                        $errorMessage = $handlerContext['error'] ?? $e->getMessage();
                        $this->setErrorLineLog(json_encode($errorMessage), ['ConnectException', $errno]);
                        $this->setErrorLog($entryLogLabel, $fileName, $failedSyncResyncPath, ErrorStatus::SYNCING_ERROR, null, 'ConnectException', json_encode($errorMessage));
                    } catch (\Exception $e) {
                        // fallback, in case of other exception                                
                        $this->setErrorLineLog(json_encode($e), ['HttpException', $apiSetup->end_point]);
                        $this->setErrorLog($entryLogLabel, $fileName, $failedSyncResyncPath, ErrorStatus::SYNCING_ERROR, null, 'HttpException', $e->getMessage());
                        sleep(10);
                    }
                }

                $this->createErrorLogFile($localDisk, $failedConversionFolderPathErrors, ErrorStatus::SYNCING_ERROR);
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

        if (! isValidJson($jsonContent)) {
            $this->fileContentErrors[] = __('message.not_valid_json_file');
            return false;
        }

        $json = json_decode($jsonContent, true);
        if (isset($json['transaction']) ) {
            $transaction = !empty($json['transaction']) ? $json['transaction'][0] : array();

            if (! isset($transaction['transaction_id'])) {
                $this->fileContentErrors[] = __('message.key_not_present', ['key' => 'transaction_id']);
            }

            $transactionType = isset($transaction['transaction_type']) ? $transaction['transaction_type'] : null;
            $isZread = isset($transaction['is_zread']) ? $transaction['is_zread'] : 0;

            if ((
                $transactionType === TerminalTransactionType::SALES
                ||  $transactionType === TerminalTransactionType::REFUND
                ||  $transactionType === TerminalTransactionType::FREE_ITEMS )
                &&  $isZread === 0
            ) {
                if (isset($transaction['official_receipt']) && ! empty($transaction['official_receipt'])) {
                    $officialReceipt = $transaction['official_receipt'][0];

                    if (! isset($officialReceipt['or_number'])) {
                        $this->fileContentErrors[] = __('message.key_not_present', ['key' => 'or_number']);
                    }
                } else {
                    $this->fileContentErrors[] = __('message.key_not_present', ['key' => 'official_receipt']);
                }
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

    private function setErrorLineLog ($message, $status = [])
    {
        $this->createLog($message, 'error', true, $status);
        $this->flushOutputBuffer();

        sleep(5);
    }

    public function doCleanup($localDisk, $syncedFolderPath, $entryLogLabel)
    {
        $fileCleanup = config('filesystems.file_cleanup');
        if ($fileCleanup) {
            $filesCount = $this->cleanupFiles($localDisk, $syncedFolderPath);
            if ($filesCount) {
                $this->createLog(__('message.file_cleaned_up', ['value' => $filesCount]), 'info', true, [$entryLogLabel]);
            }
        }
    }
}
