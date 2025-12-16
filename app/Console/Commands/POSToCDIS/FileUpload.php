<?php

namespace App\Console\Commands\POSToCDIS;

use App\Enums\CommonErrors;
use App\Enums\ReportFileType;
use App\Enums\Status;
use App\Helpers\CustomPinger as Ping;
use App\Repositories\Contracts\TerminalFileSetupRepository;
use App\Services\ErrorLogService;
use App\Traits\FileStorageSetupTrait;
use App\Traits\GenericHelper;
use App\Traits\OutputBufferTrait;
use App\Traits\StorageTrait;
use App\Traits\TerminalFileSetupTrait;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use League\Flysystem\FilesystemException;

class FileUpload extends Command implements ShouldQueue
{
    use GenericHelper, OutputBufferTrait, StorageTrait, TerminalFileSetupTrait, FileStorageSetupTrait;

    public $errorLogService;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:upload {--type=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically upload report file to CDIS';

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
        $cdisUrl = getDomain(config()->get('app.cdis_url'), true);
        $cdisDomainName = getDomain($cdisUrl, false);

        $this->line(__('info.watching_files_to_upload'));
        $this->line('');

        $type = $this->option('type') ?: null;
        $filters =(object) ['status' => Status::ACTIVE];
        if (! empty($type)) {
            $filters->type = $type;

            $typeDescription = ReportFileType::getDescription(intval($type));
            $this->line(__('info.uploading_report')." [$typeDescription]");
        }
        while (true) {

            if (! $this->hasInternetConnection() && ! $this->hasInternetConnection($cdisUrl)) {
                $this->setErrorLog(__('message.no_internet_connection'));
                continue;
            }

            $terminalFileSetups = app()->make(TerminalFileSetupRepository::class)->list($filters);
            if (count($terminalFileSetups) > 0) {
                foreach ($terminalFileSetups as $terminalFile) {
                    $apiSetup = $terminalFile->apiSetup;
                    if (empty($apiSetup)) {
                        $this->setErrorLog(__('error.no_endpoint_configured'));
                        continue;
                    }

                    $endpointDomain = getDomain($apiSetup->end_point, true);
                    if ($endpointDomain !== $cdisUrl) {
                        $this->setErrorLog(__('error.configured_endpoint_does_not_matched_to', ['value' => $cdisUrl]));
                        continue;
                    }

                    // Get local path of the POS to CDIS file storage setup
                    // to be the root source directory
                    $localPath = $this->getLocalStoragePath($terminalFile);
                    if (empty($localPath)) {
                        $this->setErrorLog(__('error.no_file_storage_setup_detected'));
                        continue;
                    }

                    if (!is_dir($localPath) || !is_readable($localPath)) {
                        $this->setErrorLog(__('error.no_file_storage_setup_detected'));
                        continue;
                    }
                    // Validate parent directory first
                    $parentDir = dirname($localPath);

                    if (!File::isDirectory($parentDir)) {
                        $this->setErrorLog("Base directory does not exist: {$parentDir}");
                        continue;
                    }
                    // initialize the target disk that handles destination
                    
                    $sourceFullPath = $localPath.'/Reports/'.cleanNonAlphaNumericChars($terminalFile->name);
                    // Check every parent folder in the chain exists
                    $fullParent = dirname($sourceFullPath);

                    if (!File::isDirectory($fullParent)) {
                        $this->setErrorLog("Cannot create directory. Base folder does not exist: {$fullParent}");
                        continue;
                    }

                    $storageDisk = $this->resolveFilesystemDisk('upload_'.cleanNonAlphaNumericChars(strtolower($terminalFile->name)), $sourceFullPath); 
                   
                    
                    $sourceDirectory = '/';
                    $destinationSubDirectoryUpload = '/Uploaded';
                    $destinationSubDirectoryErrors = '/Errors';

                    // create directory if not exist for folder Uploaded and Errors
                    $this->createDirectoryIfNotExist($storageDisk, $destinationSubDirectoryUpload);
                    $this->createDirectoryIfNotExist($storageDisk, $destinationSubDirectoryErrors);

                    // Get files inside source directory
                    $files = $storageDisk->files($sourceDirectory);

                    if (count($files)) {
                        $this->createLog(__('info.files_found_in', ['value' => $sourceFullPath]), 'info', true,[$terminalFile->name, count($files) ]);

                        foreach ($files as $file) {

                            $sourceFilenamePath = "$sourceFullPath/$file";
                            $targetFilenameError = "$destinationSubDirectoryErrors/$file";
                            $targetFilenameSuccess = "$destinationSubDirectoryUpload/$file";
                           
                             // Check if file already exist in Upload folder
                             if ($storageDisk->exists($targetFilenameSuccess)) {
                                // It means file is already been uploaded, so we need
                                // to ignore this and proceed to next file
                                $this->createLog($sourceFilenamePath, 'line', true, ['UPLOADED']);
                                continue;
                            } else {
                                $this->createLog($sourceFilenamePath, 'line', true, ['UPLOADING...']);
                            }

                            try {
                                // To avoid FatalErrorException due to allocated memory size limit,
                                // we set memory limit before reading the content of the file
                                ini_set('memory_limit', '-1');
                                $fileContent = $storageDisk->get($file);
                            } catch (\Exception $e) {
                                // Move the file to designated error folder to make sure
                                // in next run, files will not be re-included
                                $this->moveFile($storageDisk, $file, $targetFilenameError);
                                $this->createLog(json_encode($e), 'error', true, ['FileException']);
                                continue;
                            }

                            // Build necessary data to be sent into CDIS server
                            $data = [
                                'name' => $terminalFile->name,
                                'branch_code' => config('configuration.branch_code'),
                                'terminal_number' => $terminalFile->terminal_code,
                                'type' => $terminalFile->type,
                                'module_type' => \Illuminate\Support\Str::snake(ReportFileType::getDescription($terminalFile->type)),
                                'date' => $this->getDate($file, $fileContent, $terminalFile->type),
                            ];

                            try {
                                $response = $this->send($sourceFilenamePath, $data, $apiSetup);
                                $responseBodyContent = json_decode($response->getBody()->getContents());
                                $statusCode = $response->getStatusCode();
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
                                        $this->moveFile($storageDisk, $file, $targetFilenameError);
                                        sleep(5);
                                    }
                                    if ($statusCode === Response::HTTP_PRECONDITION_FAILED) {
                                        // Due to some missing references, which are a prereq on saving
                                        // attachments, we need to add some delay to make sure syncing of 
                                        // data comes first before re-uploading
                                        sleep(10);
                                    }
                                    $this->setErrorLog("{$statusText} : ".json_encode($responseBodyContent),[$statusCode]);
                                } else {
                                    if (! empty($responseBodyContent)) {
                                        if ($statusCode === Response::HTTP_OK) {
                                            $this->moveFile($storageDisk, $file, $targetFilenameSuccess);
                                            $this->createLog(json_encode($responseBodyContent), 'info', true, [Response::$statusTexts[$statusCode]]);
                                        } else {
                                            // Display warning message containing API response,
                                            // means request is successfully sent but it does not return
                                            // an expected response
                                            $this->createLog(json_encode($responseBodyContent), 'warn', true, [Response::$statusTexts[$statusCode]]);
                                        }
                                    }
                                }
                            } catch (\GuzzleHttp\Exception\TooManyRedirectsException $e) {
                                // handle too many redirects
                                $this->setErrorLog(json_encode($e), ['TooManyRedirectsException']);
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
                                        $this->moveFile($storageDisk, $file, $targetFilenameError);
                                        sleep(5);
                                    }
                                }
                                $this->setErrorLog(json_encode($e->getResponse()), ['ClientException|ServerException', $statusCode]);
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
                                $this->setErrorLog(json_encode($errorMessage), ['ConnectException', $errno]);
                            } catch (\Exception $e) {
                                // fallback, in case of other exception                                
                                $this->setErrorLog(json_encode($e), ['HttpException',$apiSetup->end_point]);
                                sleep(10);
                            }
                            sleep(5); // add time delay to avoid too many request
                        }
                    } else {
                        $this->createLog(__('info.no_files_found_in', ['value' => $sourceFullPath]), 'warn', true, [$terminalFile->name]);
                    }
                }
            } else {
                $this->createLog(__('error.no_terminal_file_setup_detected'), 'warn', true,);
            }

            $this->flushOutputBuffer();
            sleep(5);
        }
    }

    public function send($filename, $data, $apiSetup)
    {
        $client = new Client([
            'verify' => false,
            'http_errors' => false,
        ]);

        $senderDetails = ['sender_details' => $this->getSenderDetails()];
        $content = array_merge($senderDetails, $data);

        // To upload files together with other information  we use 
        // multipart option, upload both json file and form-data; 
        $options =  [
            'multipart' => [
                [
                    'name'     => 'metadata',
                    'contents' => json_encode($content),
                    'headers'  => ['Content-Type' => 'application/json']
                ],
                [
                    'name'     => 'file',
                    'contents' => fopen($filename, 'r'),
                    'headers'  => ['Content-type' => 'multipart/form-data',]
                ],
            ],
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

    private function setErrorLog($message, $status = [])
    {
        $this->createLog($message, 'error', true, $status);
        $this->flushOutputBuffer();

        sleep(5);
    }
}
