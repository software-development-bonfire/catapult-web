<?php

namespace App\Console\Commands\POSToCDIS;

use App\Enums\Status;
use App\Enums\StorageType;
use App\Exports\PosToCdisExport;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Traits\GenericHelper;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class SendDataFromConvertedFile extends Command
{
    use GenericHelper;

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
            filter_var($timeout, FILTER_VALIDATE_BOOLEAN)
                ? config('sync.pos.to_cdis.timeout')
                : (
                    (int) $timeout
                        ? filter_var($timeout, FILTER_VALIDATE_INT)
                        : false
                );

        if ($timeout <= 0 || ! is_int($timeout)) {
            $this->createLog('Timeout value must be equal or greater than 1.', 'error', true, []);

            return;
        }

        while (true) {
            $entries = [
                'transaction',
                'zread',
                'audit_trail',
                'cash_breakdown',
                'cash_drawer',
            ];

            $entriesMaxLength = max(array_map('strlen', $entries));
            $remoteDiskName = '';
            $localDiskName = '';

            $hasFilesToSync = false;

            foreach ($entries as $entry) {
                $hasException = false;
                $spaces = ($entriesMaxLength - strlen($entry)) / 2;
                $entryLogLabel = str_repeat(' ', ceil($spaces)).$entry.str_repeat(' ', floor($spaces));

                $filters = (object) [
                    'data_entry' => $entry,
                    'status' => Status::ACTIVE,
                ];

                $fieldMappingDetails = app()
                    ->make(FieldMappingRepository::class)
                    ->list($filters, false, ['fileStorageSetup', 'apiSetup']);

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

                if ($fileStorageSetup->storage_type == StorageType::FTP) {
                    $remoteDiskName = 'pos_ftp_remote_send_data_from_converted_file';
                    $localDiskName = 'pos_ftp_local_send_data_from_converted_file';

                    resolve('filesystem')->forgetDisk($remoteDiskName);
                    app()['config']->set('filesystems.disks.' . $remoteDiskName . '.driver', 'ftp');
                    app()['config']->set('filesystems.disks.' . $remoteDiskName . '.host', $fileStorageSetup->host);
                    app()['config']->set('filesystems.disks.' . $remoteDiskName . '.username', $fileStorageSetup->username);
                    app()['config']->set('filesystems.disks.' . $remoteDiskName . '.password', $fileStorageSetup->password);
                    app()['config']->set('filesystems.disks.' . $remoteDiskName . '.port', $fileStorageSetup->port);
                    app()['config']->set('filesystems.disks.' . $remoteDiskName . '.root', $fileStorageSetup->remote_path);

                    resolve('filesystem')->forgetDisk($localDiskName);
                    app()['config']->set('filesystems.disks.' . $localDiskName . '.driver', 'local');
                    app()['config']->set('filesystems.disks.' . $localDiskName . '.root', $fileStorageSetup->local_path);
                } else if ($fileStorageSetup->storage_type == StorageType::LOCAL_NETWORK) {
                    $remoteDiskName = 'pos_local_remote_send_data_from_converted_file';
                    $localDiskName = 'pos_local_local_send_data_from_converted_file';

                    resolve('filesystem')->forgetDisk($remoteDiskName);
                    app()['config']->set('filesystems.disks.' . $remoteDiskName . '.driver', 'local');
                    app()['config']->set('filesystems.disks.' . $remoteDiskName . '.root', $fileStorageSetup->remote_path);

                    resolve('filesystem')->forgetDisk($localDiskName);
                    app()['config']->set('filesystems.disks.' . $localDiskName . '.driver', 'local');
                    app()['config']->set('filesystems.disks.' . $localDiskName . '.root', $fileStorageSetup->local_path);
                } else {

                    return false;
                }

                $entryFolderName = Str::title(str_replace('_', ' ', $entry));

                $localDisk = Storage::disk($localDiskName);
                $sourcePath = $entryFolderName . '/Converted/To sync';
                $syncedPath = $entryFolderName . '/Converted/Synced';
                $failedSyncBadRequestPath = $entryFolderName . '/Converted/Failed sync/Bad request';
                $failedSyncUnsyncablePath = $entryFolderName . '/Converted/Failed sync/Unsyncable';

                $files = $localDisk->allFiles($sourcePath);

                if (! $files) {
                    $this->createLog(__('message.no_data_to_send'), 'info', true, [$entryLogLabel], []);

                    continue;
                } else {
                    $hasFilesToSync = true;
                }

                foreach ($files as $file) {
                    $fileContent = $localDisk->get($file);
                    $fileContent = (array) json_decode($fileContent);
                    $fileName = substr($file, strrpos($file, '/') + 1);

                    $responseBodyContent = null;

                    try {
                        $response = $this->send($fileContent, $apiSetup);
                        $statusCodeLabel = 'Status code: '. $response->getStatusCode();

                        $responseBodyContent = json_decode($response->getBody()->getContents());

                        $destinationPath = null;

                        if (isset($responseBodyContent->exception) || isset($responseBodyContent->trace)) {
                            $this->createLog($responseBodyContent->exception.': '.$responseBodyContent->message, 'error', true, [$entryLogLabel, $statusCodeLabel], [$file]);

                            $destinationPath = $failedSyncUnsyncablePath.'/'.$fileName;
                        } else {
                            $errors = isset($responseBodyContent->errors) ? (array) $responseBodyContent->errors : [];

                            if ((isset($responseBodyContent->success) && $responseBodyContent->success) || $responseBodyContent->message == 'Duplicate Entry.') {
                                $this->createLog($responseBodyContent->message,
                                    ($responseBodyContent->message == 'Duplicate Entry.' ? 'warn' : 'info'),
                                    true,
                                    [$entryLogLabel, $statusCodeLabel],
                                    [$file]
                                );

                                $destinationPath = $syncedPath.'/'.$fileName;
                            } else if (isset($responseBodyContent->success) && ! $responseBodyContent->success && count($errors) > 0) {
                                $this->createLog(__('error.failed_to_send_data'), 'error', true, [$entryLogLabel, $statusCodeLabel], [$file]);
                                $this->createLog('    Errors:', 'error', false);
                                foreach ($errors as $error) {
                                    $this->createLog('        -> '.$error[0], 'error', false);
                                }

                                $destinationPath = $failedSyncBadRequestPath.'/'.$fileName;
                            } else if (isset($responseBodyContent->success) && ! $responseBodyContent->success || $responseBodyContent->message == 'Request failed.') {
                                $this->createLog(__('error.failed_to_send_data'), 'error', true, [$entryLogLabel, $statusCodeLabel], [$file]);
                                $this->createLog('    Cause: '. $responseBodyContent->message, 'error', false);

                                $destinationPath = $failedSyncBadRequestPath.'/'.$fileName;
                            } else {
                                continue;
                            }
                        }

                        if ($localDisk->exists($destinationPath)) {
                            $localDisk->delete($destinationPath);
                        }

                        $localDisk->move($file, $destinationPath);
                    } catch (\Exception $exception) {
                        $this->createLog($exception->getMessage(), 'warn', true, [$entryLogLabel]);
                        $hasException = true;
                        sleep($timeout);
                        continue;
                    }

                    if (! $hasException) {
                        sleep($timeout);
                    }
                }
            }

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

    private function getSenderDetails() {
        return [
            'client_id' => config('configuration.client_id'),
            'product_key' => config('configuration.product_key'),
            'branch_code' => config('configuration.branch_code'),
            'system_datetime' => Carbon::now()->format('Y-m-d h:i:s'),
        ];
    }
}
