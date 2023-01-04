<?php

namespace App\Console\Commands\POSToCDIS;

use App\Enums\ReportFileType;
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

            $terminalFileSetups = app()->make(TerminalFileSetupRepository::class)->list($filters);
            if (count($terminalFileSetups) > 0) {
                foreach ($terminalFileSetups as $terminalFile) {
                    $apiSetup = $terminalFile->apiSetup;
                    if (empty($apiSetup)) {
                        $this->createLog(__('error.no_endpoint_configured'), 'warn', true,);
                        continue;
                    }

                    $terminalPath = $terminalFile->terminal_path;

                    $storageDisk = $this->resolveFilesystemDisk(cleanNonAlphaNumericChars(strtolower($terminalFile->name)), $terminalPath);

                    $rootSubDirectory = '/';
                    $destinationSubDirectory = '/Uploaded';
                    $this->createDirectoryIfNotExist($storageDisk, $rootSubDirectory);
                    $this->createDirectoryIfNotExist($storageDisk, $destinationSubDirectory);

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
                                'module_type' => 'e_journal',
                                'date' => $this->getContentLogDate($fileContent),

                            ];
                            $response = $this->send($filenamePath, $data, $apiSetup);
                            $statusCode = $response->getStatusCode();

                            $responseBodyContent = json_decode($response->getBody()->getContents());

                            if (! empty($responseBodyContent) && $responseBodyContent->success) {
                                $targetFilename = "$destinationSubDirectory/$file";

                                $storageDisk->put($targetFilename, $storageDisk->get($file));
    
                                if ($storageDisk->exists($targetFilename)) {
                                    $storageDisk->delete($file);
                                }                           
                            } else {

                            }
                                
                            $this->createLog("Status code: $statusCode", 'info', true,);    
                            $this->createLog(json_encode($responseBodyContent), 'warn', true,);
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

    private function getContentLogDate($content)
    {
        $logDate = Carbon::now();
        $pattern = "/Log Date.*: (.*)/";
        if (preg_match_all($pattern, $content, $matches)) {
            $match = implode(",", $matches[0]);
            if (!empty($match)) {
                $chunks =  explode(":", $match);
                if (!empty($chunks) && count($chunks) > 1) {
                    $logDate = trim($chunks[1]);
                }
            }
        }
        return $logDate;
    }
}
