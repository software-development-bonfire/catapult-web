<?php

namespace App\Console\Commands\POSToCDIS;

use App\Enums\CommonErrors;
use App\Enums\ReportFileType;
use App\Helpers\CustomPinger as Ping;
use App\Repositories\Contracts\TerminalFileSetupRepository;
use App\Services\ErrorLogService;
use App\Traits\GenericHelper;
use App\Traits\OutputBufferTrait;
use App\Traits\StorageTrait;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class EJournalUploader extends Command implements ShouldQueue
{
    use GenericHelper, OutputBufferTrait, StorageTrait;

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
        $filters = null;
        if (! empty($type)) {
            $filters = (object) ['type' => $type];

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

                    $terminalPath = $terminalFile->terminal_path;

                    $storageDisk = $this->resolveFilesystemDisk(cleanNonAlphaNumericChars(strtolower($terminalFile->name)), $terminalPath);

                    $rootSubDirectory = '/';
                    $destinationSubDirectoryUpload = '/Uploaded';
                    $destinationSubDirectoryErrors = '/Errors';
                    $this->createDirectoryIfNotExist($storageDisk, $rootSubDirectory);
                    $this->createDirectoryIfNotExist($storageDisk, $destinationSubDirectoryUpload);
                    $this->createDirectoryIfNotExist($storageDisk, $destinationSubDirectoryErrors);

                    $files = $storageDisk->files($rootSubDirectory);
                    if (count($files)) {
                        foreach ($files as $file) {
                            $fileContent = $storageDisk->get($file);

                            $filenamePath = "$terminalPath/$file";

                            $this->createLog($file, 'info', true,);
                            $data = [
                                'name' => $terminalFile->name,
                                'branch_code' => config('configuration.branch_code'),
                                'terminal_number' => $terminalFile->terminal_code,
                                'type' => $terminalFile->type,
                                'module_type' => \Illuminate\Support\Str::snake(ReportFileType::getDescription($terminalFile->type)),
                                'date' => $this->getDate($file, $fileContent, $terminalFile->type),
                            ];

                            $response = $this->send($filenamePath, $data, $apiSetup);
                            $statusCode = $response->getStatusCode();

                            $responseBodyContent = json_decode($response->getBody()->getContents());

                            if (! empty($responseBodyContent)) {
                                $targetFilename = "$destinationSubDirectoryUpload/$file";
                                if (
                                    $responseBodyContent->success &&
                                    (isset($responseBodyContent->message)
                                    && \Illuminate\Support\Str::contains($responseBodyContent->message, "successfully uploaded")
                                    )
                                ) {
                                    $targetFilename = "$destinationSubDirectoryUpload/$file";
                                }

                                if (! empty($responseBodyContent->errors)) {
                                    if (\Illuminate\Support\Str::contains(
                                        $responseBodyContent->errors,
                                        [
                                            CommonErrors::NO_FILE_REPORT_FOUND,
                                            CommonErrors::NOT_ALLOWED_FILE_EXT
                                        ]
                                    )) {
                                        $targetFilename = "$destinationSubDirectoryErrors/$file";
                                    }
                                }

                                $this->moveFile($storageDisk, $file, $targetFilename);
                            } else {
                                if ($statusCode === 429) {
                                    sleep(10);
                                }
                            }
                                
                            $this->createLog("Status code: $statusCode", 'info', true,);    
                            $this->createLog(json_encode($responseBodyContent), 'warn', true,);
                            sleep(5); // add time delay to avoid too many request
                        }
                    } else {
                        $this->createLog(__('info.no_files_found_in', ['value' => $terminalPath]), 'warn', true,);
                    }
                }
            } else {
                $this->createLog(__('error.no_terminal_file_setup_detected'), 'warn', true,);
                continue;
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

    private function getDate($file, $content, $reportFileType)
    {
        $date = Carbon::now();

        if ($reportFileType === ReportFileType::Z_READING || $reportFileType === ReportFileType::SALES_TRANSACTIONS) {

            $pattern = "/Log Date.*: (.*)/";
            if ($reportFileType === ReportFileType::SALES_TRANSACTIONS) {
                $pattern = "/(LOGDATE.*):(.*)/";
            }

            if (preg_match_all($pattern, $content, $matches)) {
                $match = implode(",", $matches[0]);
                if (! empty($match)) {
                    $chunks =  explode(":", $match);
                    if (! empty($chunks) && count($chunks) > 1) {
                        $date = trim($chunks[1]);
                        if (! empty($date)) {
                            $date = date_create_from_format("m/d/Y", $date);
                            if (! empty($date)) {
                                $date = date_format($date, "Y-m-d");
                            }
                        }
                    }
                }
            }
        } else if ($reportFileType === ReportFileType::JOURNAL_REPORTS) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            $createdDate = date_create_from_format("mdY", $filename);
            if (! empty($createdDate)) {
                $date = date_format($createdDate, "Y-m-d");
            }
        } else {
        }
        return $date;
    }

    private function setErrorLog ($message) {
        $this->createLog($message, 'error', true);
        $this->flushOutputBuffer();

        sleep(5);
    }
}
