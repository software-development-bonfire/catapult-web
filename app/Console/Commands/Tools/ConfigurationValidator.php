<?php

namespace App\Console\Commands\Tools;

use App\Enums\Status;
use App\Helpers\CustomNetworkResolver;
use App\Helpers\CustomPinger as Ping;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Traits\GenericHelper;
use App\Traits\StorageTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ConfigurationValidator extends Command
{
    use GenericHelper;
    use StorageTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute defined network command, to resolve issue on network protocol and DNS caching';

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
        $cdisUrl = config()->get('app.cdis_url'); 
        $cdisHost = getDomain($cdisUrl, false);
        $cdisHostScheme = getDomain($cdisUrl, true);
        $pusherhost = 'ws-eu.pusher.com';

        // This section will initialize the project by calling
        // optimize:clear command to clear all system caches and 
        // compiled services and packages. Then we make sure to
        // re-generate app key of the system
        $this->line("INITIALIZE...");
        $this->call('optimize:clear');
        $this->call('key:generate');
        $this->call('optimize');

        // This section will check internet connection by calling
        // $hasInternetConnection generic helper and we need to ping
        // CDIS Host to check server connection is available
        $this->line("NETWORK CONNECTION...");
        if ( $this->hasInternetConnection() && $this->hasInternetConnection($cdisHost)) {
            $this->info("[✔] Internet connection!");
        } else {
            $this->error("[✖] Internet connection: ".__('message.no_internet_connection'));
        }

        $ping = new Ping($cdisHost);
        $latency = $ping->ping();
        if ($latency) {
            $this->info("[✔] Pinging: ".$cdisHost);
        } else {
            $this->error("[✖] Pinging: ".__('error.no_ping_response_from_host', ['value' => $cdisHost]));
        }

        // This section will check configured file storage and API setup of
        // Catapult. It will check field mapping and field mapping directory existence.
        // It conducts also basic endpoints validation to check if it returns valid response
        // from server. It returns count of files inside directories.
        $this->line("FILE STORAGE AND API SETUP...");
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

            $filters = (object) [
                'data_entry' => $entry,
                'status' => Status::ACTIVE,
            ];
            $fieldMappingDetails  = app()->make(FieldMappingRepository::class)->list($filters, false, ['fileStorageSetup', 'apiSetup']);
            if (count($fieldMappingDetails) > 0) {
                if ($fieldMappingDetails[0]) {
                    $fieldMappingDetails = $fieldMappingDetails[0];
                    $apiSetup = $fieldMappingDetails->apiSetup;

                    $endpointDomain = getDomain($apiSetup->end_point, true);
                    if ($endpointDomain !== $cdisHostScheme) {
                        $this->error("[✖] API Setup for [".$entryLogLabel."]: ".__('error.configured_endpoint_does_not_matched_to', ['value' => $cdisHostScheme]));
                        $this->error("             : ".$apiSetup->end_point);
                    } else {
                        $response = $this->send([], $apiSetup);
                        $statusCode = $response->getStatusCode();
                        $content = json_decode($response->getBody()->getContents());
                        $message = isset($content->message) ? $content->message : "";
                        if (empty($message)) {
                            if (isset($content->exception) || isset($content->trace)) {
                                $message = isset($content->exception) ? $content->exception : (isset($content->trace) ? $content->trace : "{}");
                            }
                        }

                        if ($statusCode >= 200 && $statusCode <= 400 ) {
                            $this->info("[✔] API Setup for [{$entryLogLabel}]: Status Code {$statusCode} => {$apiSetup->end_point}");
                        } else {
                            $this->error("[✖] API Setup for [{$entryLogLabel}]: Status Code {$statusCode} => ".json_encode($message));
                            $this->error("                   [{$entryLogLabel}]: {$apiSetup->end_point}");
                        }
                    }

                    $fileStorageSetup = $fieldMappingDetails->fileStorageSetup;

                    $selectedDisk = $this->intializeDisk($fileStorageSetup, \App\Enums\StorageCommandSelection::RESEND);
                    if (isset($selectedDisk) && is_array($selectedDisk)) {
                        $remoteDiskName = $selectedDisk['remoteDiskName'];
                        $localDiskName = $selectedDisk['localDiskName'];
                   
                        $entryFolderName = \Illuminate\Support\Str::title(str_replace('_', ' ', $entry));

                        $localDisk = Storage::disk($localDiskName);

                        $directories = array (
                            [
                                'name' => 'To sync',
                                'path' =>  $entryFolderName.'/Converted/To sync',
                                'must_zero' => false,
                            ],
                            [
                                'name' => 'Resync',
                                'path' =>  $entryFolderName.'/Converted/Failed sync/Resync',
                                'must_zero' => true,
                            ],
                            [
                                'name' => 'Unsyncable',
                                'path' =>  $entryFolderName.'/Converted/Failed sync/Unsyncable',
                                'must_zero' => true,
                            ],
                            [
                                'name' => 'To convert',
                                'path' => $entryFolderName.'/To convert',
                                'must_zero' => false,
                            ],
                            [
                                'name' => 'Errors',
                                'path' => $entryFolderName.'/Failed conversion/Errors',
                                'must_zero' => true,
                            ],
                        );
                        $entriesMaxLength = max(array_map('strlen', $entries));
                        foreach ($directories as $directory) {
                            $path = $directory['path'];
                            $name = $directory['name'];
                            $mustZero = $directory['must_zero'];
                            $spaces = ($entriesMaxLength - strlen($name)) / 2;
                            $folderLabel = str_repeat(' ', ceil($spaces)).$name.str_repeat(' ', floor($spaces));

                            if ($localDisk->exists($path)) {
                                $files = $localDisk->files($path);
                                $filesCount = count($files);
                                if ($filesCount > 0 && $mustZero) {
                                    $this->warn("[⚠] Directory for [{$entryLogLabel}]: <{$folderLabel}> directory contains <{$filesCount}> files. ({$path})");
                                } else {
                                    $this->line("[⚠] Directory for [{$entryLogLabel}]: <{$folderLabel}> directory contains <{$filesCount}> files. ({$path})");
                                }
                               
                            } else {
                                $this->warn("[⚠] Directory for [{$entryLogLabel}]: <{$folderLabel}> Not exist directory. ({$path}) ");
                            }
                        }
                    }

                }
            } else {
                $this->warn("[⚠] API Setup for [{$entryLogLabel}]: ".__('error.no_api_setup_detected'));
            }
        }     
        
        // This section will check pending jobs and failed jobs
        $this->line("JOBS...");
        $tableJobs = ['jobs', 'failed_jobs'];
        $entriesMaxLength = max(array_map('strlen', $tableJobs));
        foreach ($tableJobs as $table) {
            $spaces = ($entriesMaxLength - strlen($table)) / 2;
            $jobLabel = str_repeat(' ', ceil($spaces)).$table.str_repeat(' ', floor($spaces));

            $jobs = DB::table($table)->get();
            $jobsCount = count($jobs);
            if ($jobsCount > 0) {
                $this->warn("[⚠] Jobs [{$jobLabel}]: contains <{$jobsCount}> jobs");
            } else {
                $this->info("[✔] Jobs [{$jobLabel}]: contains <{$jobsCount}> jobs");
            }
        }

    }

    public function urlExists($url) {

        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

        $response = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if($httpCode >= 200 && $httpCode <= 400) {
            return true;
        } else {
            return false;
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
}
