<?php

namespace App\Console\Commands\CDISToPOS;

use App\Entities\CDISSync;
use App\Entities\ErrorLog;
use App\Entities\ErrorLogDetail;
use App\Entities\FieldMapping;
use App\Enums\Disk;
use App\Enums\MappingType;
use App\Enums\Status;
use App\Enums\StorageType;
use App\Exports\CDIS\DataConversionToExcel;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Repositories\Contracts\SyncEntryRepository;
use App\Traits\DatabaseTransaction;
use App\Traits\GenericHelper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ConvertDataToFile extends Command
{
    use DatabaseTransaction, GenericHelper;

    public $extension = 'csv';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:convert-data-to-file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert CDIS data to specific file';

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
        Cache::forget('excludedEntries');
        Cache::forget('excludedSyncBids');

        $this->createLog(
            __('info.syncing_started'),
            'info',
            true
        );

        $filters = (object) array('type' => MappingType::CDIS_TO_POS);
        $syncEntries = app()->make(SyncEntryRepository::class)->list($filters);

        foreach ($syncEntries as $syncEntry) {
            $this->syncEntries[$syncEntry->name] = $syncEntry->alias;
        }

        while (true) {
            $excludedEntries = Cache::get('excludedEntries') ?? [];
            $excludedSyncBids = Cache::get('excludedSyncBids') ?? [];

            $forSyncData = CDISSync::whereLevel(1)
                ->orderBy('created_at', 'ASC');

            if ($excludedEntries && is_array($excludedEntries)) {
                $forSyncData = $forSyncData->whereNotIn('table_name', $excludedEntries);
            }

            if ($excludedSyncBids && is_array($excludedSyncBids)) {
                $forSyncData = $forSyncData->whereNotIn('bid', $excludedSyncBids);
            }

            $forSyncData = $forSyncData->limit(60)->get();

            if ($forSyncData->count() <= 0) {
                foreach ($excludedEntries as $excludedEntry) {
                    $this->createLog(
                        __('error.no_field_mapping_detected'),
                        'error',
                        true,
                        [],
                        [$excludedEntry]
                    );
                }

                $this->createLog(
                    __('message.no_data_to_convert_to_value', ['value' => $this->extension]),
                    'info',
                    true
                );

                sleep(5);
                continue;
            }

            foreach ($forSyncData as $forSyncDatum) {
                $remoteDiskName = '';
                $localDiskName = '';

                $entryName = $forSyncDatum->table_name;

                $filters = (object) [
                    'data_entry' => $entryName,
                    'status' => Status::ACTIVE,
                ];

                $fieldMappingDetails = app()
                    ->make(FieldMappingRepository::class)
                    ->list($filters, false, ['remoteSetup']);

                if (count($fieldMappingDetails) > 0) {
                    $fieldMappingDetails = $fieldMappingDetails[0];
                } else {
                    $this->cacheExcludedValue('excludedEntries', $entryName);
                    $this->createLog(
                        __('error.no_field_mapping_detected'),
                        'error',
                        true,
                        [],
                        [$entryName]
                    );

                    break;
                }

                $remoteSetup = $fieldMappingDetails->remoteSetup;

                if ($remoteSetup->storage_type == StorageType::FTP) {
                    $remoteDiskName = 'cdis_ftp_remote_sync_data_file';
                    $localDiskName = 'cdis_ftp_local_sync_data_file';

                    resolve('filesystem')->forgetDisk($remoteDiskName);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'ftp');
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.host', $remoteSetup->host);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.username', $remoteSetup->username);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.password', $remoteSetup->password);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.port', $remoteSetup->port);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $remoteSetup->remote_path);

                    resolve('filesystem')->forgetDisk($localDiskName);
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $remoteSetup->local_path);
                } else if ($remoteSetup->storage_type == StorageType::LOCAL_NETWORK) {
                    $remoteDiskName = 'cdis_local_remote_sync_data_file';
                    $localDiskName = 'cdis_local_local_sync_data_file';

                    resolve('filesystem')->forgetDisk($remoteDiskName);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'local');
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $remoteSetup->remote_path);

                    resolve('filesystem')->forgetDisk($localDiskName);
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $remoteSetup->local_path);
                } else {
                    $error_log = ErrorLog::create([
                        'pos_entry' => 'Remote Setup module',
                        'filename' => 'N/A',
                        'status' => 'Failed conversion'
                    ]);

                    ErrorLogDetail::create([
                        'error_log_bid' => $error_log->bid,
                        'sheet' => 'N/A',
                        'error_type' => 'Invalid data',
                        'description' => "No configuration found, Please add configuration in Remote Setup Module."
                    ]);

                    return false;
                }

                if ($forSyncDatum) {
                    $this->processConversion($forSyncDatum, $localDiskName, $remoteSetup);
                } else {
                    $this->info(__('message.no_data_to_convert_to_file'));
                }
            }

            sleep(5);
        }
    }

    /**
     * Process conversion of CDIS data to excel file
     *
     * @param object  $forSyncDatum
     * @param string  $localDiskName
     * @param object  $remoteSetup
     *
     * @return mixed
     */
    public function processConversion($forSyncDatum, $localDiskName, $remoteSetup)
    {
        $action = $forSyncDatum->action == 'create'
            ? 'C_'
            : ($forSyncDatum->action == 'update'
                ? 'U_'
                : 'D_');

        if ($forSyncDatum->group) {
            $groupedEntrySymbol = $this->getSyncEntryAlias($forSyncDatum->table_name);

            if (is_null($groupedEntrySymbol)) {
                $this->cacheExcludedValue('excludedSyncBids', $forSyncDatum->bid);
                return false;
            }

            $toSyncData = CDISSync::where([
                'branch_bid' => $forSyncDatum->branch_bid,
                'group' => $forSyncDatum->group,
                'code' => $forSyncDatum->code,
                'action' => $forSyncDatum->action
            ])->get();

            $result = $this->generateGroupedExcelFile($toSyncData, $forSyncDatum, $action, $localDiskName, $groupedEntrySymbol);
        } else {
            $entrySymbol = $this->getSyncEntryAlias($forSyncDatum->table_name);

            if (is_null($entrySymbol)) {
                $this->cacheExcludedValue('excludedSyncBids', $forSyncDatum->bid);
                return false;
            }

            $result = $this->generateExcelFile($forSyncDatum, $action, $localDiskName, $entrySymbol);
        }

        return $result;
    }

    /**
     * Generate grouped excel file.
     *
     * @param object  $forSyncDatum
     * @param string  $action
     * @param string  $localDiskName
     * @param string  $entrySymbol
     */
    public function generateExcelFile($forSyncDatum, $action, $localDiskName, $entrySymbol)
    {
        return $this->transaction(function() use ($forSyncDatum, $action, $localDiskName, $entrySymbol) {
            $folderTimeStamp = Carbon::now()->format('mdY_His_v');

            $result = $this->mapData([$forSyncDatum], $forSyncDatum);

            if ($result) {
                $fileName = $action.$entrySymbol.'_'.$folderTimeStamp;
                $filePath = '/'.$forSyncDatum->branch_bid.'/'.$fileName.'_'.$forSyncDatum->level.'.'.$this->extension;

                $isExcelCreated = Excel::store(
                    new DataConversionToExcel($result->headers[0], $result->values[0], $this->extension),
                    $filePath,
                    $localDiskName);

                if ($isExcelCreated) {
                    $this->createLog(
                        $filePath .' created',
                        'info',
                        true,
                        [],
                        [$forSyncDatum->table_name]
                    );

                    if ($forSyncDatum->action == 'delete') {
                        $entityName = str_replace('_', '', Str::title($forSyncDatum->table_name));
                        $entity = "App\\Entities\\CDIS".$entityName;
                        $entity::where('bid', $forSyncDatum->table_bid)->delete();
                    }

                    CDISSync::find($forSyncDatum->bid)->delete();

                    return true;
                } else {
                    return false;
                }
            } else {
                $this->createLog(__('info.no_data_found'), 'info', true, [], [$forSyncDatum->table_name]);
                $this->createLog('      bid: '.$forSyncDatum->table_bid, 'warn', false);
                $this->createLog('      level: '.$forSyncDatum->level, 'warn', false);
                $this->createLog('      action: '.$forSyncDatum->action, 'warn', false);

                CDISSync::where([
                    'table_name' => $forSyncDatum->table_name,
                    'table_bid' => $forSyncDatum->table_bid,
                ])->delete();

                return false;
            }
        });
    }
    
    /**
     * Generate grouped excel file.
     * 
     * @param object  $toSyncData
     * @param object  $forSyncDatum
     * @param string  $action
     * @param string  $localDiskName
     * @param string  $groupedEntrySymbol
     *
     * @return boolean
     */
    public function generateGroupedExcelFile($toSyncData, $forSyncDatum, $action, $localDiskName, $groupedEntrySymbol)
    {
        return $this->transaction(function() use($toSyncData, $forSyncDatum, $action, $localDiskName, $groupedEntrySymbol) {
            $folderTimeStamp = Carbon::now()->format('mdY_his_v');

            $result = $this->mapData($toSyncData, $forSyncDatum);

            if ($result) {
                try {
                    $groupEntrySymbol = $groupedEntrySymbol;
                    $folderName = $action.$groupEntrySymbol.'_'.$folderTimeStamp;

                    $this->createLog($folderName .' created', 'info', true, [], [$forSyncDatum->table_name]);

                    foreach ($result->headers as $key => $header) {
                        $tableName = $toSyncData[$key]->table_name;
                        $level = $toSyncData[$key]->level;
                        $entrySymbol = $this->getSyncEntryAlias($tableName);
                        $fileName = $action.$entrySymbol.'_'.$folderTimeStamp;

                        $filePath = '/'.$forSyncDatum->branch_bid.'/'.$folderName.'/'.$fileName.'_'.$level.'.'.$this->extension;

                        $isExcelCreated = Excel::store(
                            new DataConversionToExcel($result->headers[$key], $result->values[$key], $this->extension),
                            $filePath,
                            $localDiskName);

                        if ($isExcelCreated) {
                            $this->info('       '.$filePath);

                            CDISSync::whereIn('bid', array_column($toSyncData->toArray(), 'bid'))->delete();
                        } else {
                            $this->warn($folderName .' failed to create. (Grouped)');
                        }
                    }

                    return true;
                } catch (\Exception $ex) {
                    $this->warn(Lang::get('error.conversion_failed'). '(Grouped)');

                    return false;
                }
            } else {
                return false;
            }
        });
    }

    /**
     * Map and validate data.
     * 
     * @param  mixed  $toSyncData
     * @param  object  $forSyncDatum
     *
     * @return mixed
     */
    public function mapData($toSyncData, $forSyncDatum)
    {
        $headers = [];
        $values = [];

        foreach ($toSyncData as $key => $data) {
            $entityName = str_replace('_', '', Str::title($data->table_name));
            $entity = "App\\Entities\\CDIS".$entityName;

            $hasSoftDeleting = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($entity));

            $entryData = $entity::where('bid', $data->table_bid);

            if ($hasSoftDeleting) {
                $entryData = $entryData->withTrashed();
            }

            $entryData = $entryData->first();

            if (! $entryData) {
                return null;
                break;
            }

            $fieldMapping = FieldMapping::with('dataMappings')
                ->where([
                    'type' => MappingType::CDIS_TO_POS,
                    'data_entry' => $data->table_name,
                    'status' => Status::ACTIVE
                ])->first();

            $dataToMap = []; $validate = [];

            if ($fieldMapping) {
                foreach ($fieldMapping->dataMappings as $key => $data) {
                    array_push($dataToMap, $data->column_name === '""' ? $data->field : $data->column_name);

                    array_push($validate, [
                        "field" => $data->column_name === '""' ? $data->field : $data->column_name,
                        "required" => $data->required === 1 ? "required" : "sometimes",
                        "data_type" => ''
                    ]);
                }

                $headers[] = $dataToMap;
            } else {
                $this->createLog(
                    __('error.no_field_mapping_detected'),
                    'error',
                    true,
                    [$data->table_name]
                );

                $this->createError($forSyncDatum, str_replace('_', ' ', Str::title($data->table_name)));
                return false;
            }

            foreach ($validate as $key => $valid) {
                $array_key = array_search($validate[$key]['field'], $dataToMap);
                $validate[$key]['field'] = $array_key;
            }

            $values[] = array_values($entryData->only($dataToMap));
        }

        $result = (object) array(
            'values' => $values,
            'headers' => $headers
        );

        return $result;
    }

    /**
     * Create error resource.
     * 
     * @param  object  $forSyncDatum
     * @param  string  $tableName
     */
    public function createError($forSyncDatum, $tableName = null)
    {
        $errorExist = ErrorLog::where(['pos_entry' => $forSyncDatum->table_name, 'filename' => 'N/A'])->first();
        if (! $errorExist) {
            $errorLog = ErrorLog::create([
                'pos_entry' => $forSyncDatum->table_name,
                'filename' => 'N/A',
                'status' => Lang::get('error.failed_conversion')
            ]);
            ErrorLogDetail::create([
                'error_log_bid' => $errorLog->bid,
                'sheet' => 'N/A',
                'error_type' => 'Invalid data',
                'description' => "No column found, Please add configuration in Field Mapping. ".$tableName
            ]);
        }
    }

    /**
     * Get sync entry alias.
     *
     * @param  string  $name
     */
    public function getSyncEntryAlias($name = null)
    {
        $entries = $this->syncEntries;

        return is_null($name) ? $entries : $entries[$name];
    }
}
