<?php

namespace App\Console\Commands\CDISToPOS;

use App\Entities\CDISSync;
use App\Entities\ErrorLog;
use App\Entities\ErrorLogDetail;
use App\Entities\FieldMapping;
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
use Illuminate\Support\Arr;
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

        $syncEntries = app()->make(SyncEntryRepository::class)
            ->list((object) array('type' => MappingType::CDIS_TO_POS));

        foreach ($syncEntries as $syncEntry) {
            $this->syncEntries[$syncEntry->name] = $syncEntry->alias;
        }

        $fieldMappingDetails = app()
            ->make(FieldMappingRepository::class)
            ->list(
                (object) [
                    'is_customized_mapping' => 1,
                    'status' => Status::ACTIVE,
                    'type' => 1,
                ],
                false,
                ['fileStorageSetup', 'dataMappings']
            );

        while (true) {
            if (Cache::forget('cdis_fetching_data_for_sync')) {
                sleep(1);
            }

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
                $entryName = $forSyncDatum->table_name;

                $this->processNonCustomizedMapping($entryName, $forSyncDatum);
//                $this->processCustomizedMapping($entryName, $forSyncDatum, $fieldMappingDetails);
            }

            sleep(5);
        }
    }

    public function processNonCustomizedMapping($entryName, $forSyncDatum)
    {
        $remoteDiskName = '';
        $localDiskName = '';

        $filters = (object) [
            'data_entry' => $entryName,
            'status' => Status::ACTIVE,
            'type' => 1,
        ];

        $fieldMappingDetails = app()
            ->make(FieldMappingRepository::class)
            ->list($filters, false, ['fileStorageSetup']);

        if (count($fieldMappingDetails) > 0) {
            $fieldMappingDetails = $fieldMappingDetails[0];
        } else {
            $this->cacheSetOfValue('excludedEntries', $entryName);
            $this->createLog(
                __('error.no_field_mapping_detected'),
                'error',
                true,
                [],
                [$entryName]
            );

            return null;
        }

        $fileStorageSetup = $fieldMappingDetails->fileStorageSetup;

        if ($fileStorageSetup->storage_type == StorageType::FTP) {
            $remoteDiskName = 'cdis_ftp_remote_convert_data_to_file';
            $localDiskName = 'cdis_ftp_local_convert_data_to_file';

            resolve('filesystem')->forgetDisk($remoteDiskName);
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'ftp');
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.host', $fileStorageSetup->host);
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.username', $fileStorageSetup->username);
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.password', $fileStorageSetup->password);
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.port', $fileStorageSetup->port);
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $fileStorageSetup->remote_path);

            resolve('filesystem')->forgetDisk($localDiskName);
            app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
            app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $fileStorageSetup->local_path);
        } else if ($fileStorageSetup->storage_type == StorageType::LOCAL_NETWORK) {
            $remoteDiskName = 'cdis_local_remote_convert_data_to_file';
            $localDiskName = 'cdis_local_local_convert_data_to_file';

            resolve('filesystem')->forgetDisk($remoteDiskName);
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'local');
            app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $fileStorageSetup->remote_path);

            resolve('filesystem')->forgetDisk($localDiskName);
            app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
            app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $fileStorageSetup->local_path);
        } else {
            return false;
        }

        if ($forSyncDatum) {
            $this->processNonCustomizedMappingConversion($forSyncDatum, $localDiskName, $fileStorageSetup);
        } else {
            $this->info(__('message.no_data_to_convert_to_file'));
        }
    }

    /**
     * Process non-customized mapping conversion of CDIS data to excel file
     *
     * @param object  $forSyncDatum
     * @param string  $localDiskName
     * @param object  $fileStorageSetup
     *
     * @return mixed
     */
    public function processNonCustomizedMappingConversion($forSyncDatum, $localDiskName, $fileStorageSetup)
    {
        $action = $forSyncDatum->action == 'create'
            ? 'C_'
            : ($forSyncDatum->action == 'update'
                ? 'U_'
                : 'D_');

        if ($forSyncDatum->group) {
            $groupedEntrySymbol = $this->getSyncEntryAlias($forSyncDatum->table_name);

            if (is_null($groupedEntrySymbol)) {
                $this->cacheSetOfValue('excludedSyncBids', $forSyncDatum->bid);
                return false;
            }

            $toSyncData = CDISSync::where([
                'branch_bid' => $forSyncDatum->branch_bid,
                'group' => $forSyncDatum->group,
                'code' => $forSyncDatum->code,
                'action' => $forSyncDatum->action
            ])->get();

            $result = $this->generateNonCustomizedMappingGroupedExcelFile($toSyncData, $forSyncDatum, $action, $localDiskName, $groupedEntrySymbol);
        } else {
            $entrySymbol = $this->getSyncEntryAlias($forSyncDatum->table_name);

            if (is_null($entrySymbol)) {
                $this->cacheSetOfValue('excludedSyncBids', $forSyncDatum->bid);
                return false;
            }

            $result = $this->generateNonCustomizedMappingExcelFile($forSyncDatum, $action, $localDiskName, $entrySymbol);
        }

        return $result;
    }

    /**
     * Generate non-customized mapping excel file.
     *
     * @param object  $forSyncDatum
     * @param string  $action
     * @param string  $localDiskName
     * @param string  $entrySymbol
     */
    public function generateNonCustomizedMappingExcelFile($forSyncDatum, $action, $localDiskName, $entrySymbol)
    {
        return $this->transaction(function() use ($forSyncDatum, $action, $localDiskName, $entrySymbol) {
            $folderTimeStamp = Carbon::now()->format('mdY_His_v');

            $result = $this->nonCustomizedMappingData([$forSyncDatum], $forSyncDatum);

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
     * Generate non-customized mapping grouped excel file.
     * 
     * @param object  $toSyncData
     * @param object  $forSyncDatum
     * @param string  $action
     * @param string  $localDiskName
     * @param string  $groupedEntrySymbol
     *
     * @return boolean
     */
    public function generateNonCustomizedMappingGroupedExcelFile($toSyncData, $forSyncDatum, $action, $localDiskName, $groupedEntrySymbol)
    {
        return $this->transaction(function() use($toSyncData, $forSyncDatum, $action, $localDiskName, $groupedEntrySymbol) {
            $folderTimeStamp = Carbon::now()->format('mdY_his_v');

            $result = $this->nonCustomizedMappingData($toSyncData, $forSyncDatum);

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
    public function nonCustomizedMappingData($toSyncData, $forSyncDatum)
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
     * Process customized mapping
     *
     * @param object  $forSyncDatum
     * @param string  $localDiskName
     * @param object  $fieldMappingDetails
     *
     * @return mixed
     */
    public function processCustomizedMapping($entryName, $forSyncDatum, $fieldMappingDetails)
    {
        $remoteDiskName = '';
        $localDiskName = '';

        if ($forSyncDatum) {
            $this->processCustomizedMappingConversion($forSyncDatum, $localDiskName, $fieldMappingDetails);
        } else {
            $this->info(__('message.no_data_to_convert_to_file'));
        }
    }

    /**
     * Process customized mapping conversion of CDIS data to excel file
     *
     * @param object  $forSyncDatum
     * @param string  $localDiskName
     * @param object  $fieldMappingDetails
     *
     * @return mixed
     */
    public function processCustomizedMappingConversion($forSyncDatum, $localDiskName, $fieldMappingDetails)
    {
        if ($forSyncDatum->group) {
            $toSyncData = CDISSync::where([
                'branch_bid' => $forSyncDatum->branch_bid,
                'group' => $forSyncDatum->group,
                'code' => $forSyncDatum->code,
                'action' => $forSyncDatum->action
            ])->get();

            $result = $this->generateCustomizedMappingGroupedExcelFile($toSyncData, $forSyncDatum, $localDiskName, $fieldMappingDetails);
        } else {
            $entrySymbol = $this->getSyncEntryAlias($forSyncDatum->table_name);

            if (is_null($entrySymbol)) {
                $this->cacheSetOfValue('excludedSyncBids', $forSyncDatum->bid);
                return false;
            }

            $result = $this->generateCustomizedMappingExcelFile($forSyncDatum, $localDiskName, $fieldMappingDetails);
        }

        return $result;
    }

    public function generateCustomizedMappingGroupedExcelFile($toSyncData, $forSyncDatum, $localDiskName, $fieldMappingDetails)
    {
        $data = [];
        foreach ($toSyncData as $key => $datum) {
            $entityName = str_replace('_', '', Str::title($datum->table_name));
            $entity = "App\\Entities\\CDIS".$entityName;

            $hasSoftDeleting = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($entity));

            $entryData = $entity::where('bid', $datum->table_bid);
            $tableColumns = app()->make($entity)->getTableColumns();

            if ($hasSoftDeleting) {
                $entryData = $entryData->withTrashed();
            }

            $entryData = $entryData->first();

            $data[$datum->table_name]['table_columns'] = $tableColumns;
            $data[$datum->table_name]['data'][] = $entryData->toArray();
        }

        $mappedData = [];
        foreach ($fieldMappingDetails as $fieldMappingDetail) {
            $fileStorageSetup = $fieldMappingDetail->fileStorageSetup;
            $dataMappings = $fieldMappingDetail->dataMappings->toArray();

            if ($fileStorageSetup->storage_type == StorageType::FTP) {
                $remoteDiskName = 'cdis_ftp_remote_convert_data_to_file';
                $localDiskName = 'cdis_ftp_local_convert_data_to_file';

                resolve('filesystem')->forgetDisk($remoteDiskName);
                app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'ftp');
                app()['config']->set('filesystems.disks.'.$remoteDiskName.'.host', $fileStorageSetup->host);
                app()['config']->set('filesystems.disks.'.$remoteDiskName.'.username', $fileStorageSetup->username);
                app()['config']->set('filesystems.disks.'.$remoteDiskName.'.password', $fileStorageSetup->password);
                app()['config']->set('filesystems.disks.'.$remoteDiskName.'.port', $fileStorageSetup->port);
                app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $fileStorageSetup->remote_path);

                resolve('filesystem')->forgetDisk($localDiskName);
                app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
                app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $fileStorageSetup->local_path);
            } else if ($fileStorageSetup->storage_type == StorageType::LOCAL_NETWORK) {
                $remoteDiskName = 'cdis_local_remote_convert_data_to_file';
                $localDiskName = 'cdis_local_local_convert_data_to_file';

                resolve('filesystem')->forgetDisk($remoteDiskName);
                app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'local');
                app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $fileStorageSetup->remote_path);

                resolve('filesystem')->forgetDisk($localDiskName);
                app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
                app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $fileStorageSetup->local_path);
            } else {
                break;
            }

            $columnsExistsInMapping = [];

            foreach ($data as $key => $datum) {
                foreach ($datum['table_columns'] as $tableColumn) {
                    $fieldName = $key.'.'.$tableColumn;
                    $filteredMapping = array_filter($dataMappings, function($value) use($fieldName) {
                        return strpos($value['field'], $fieldName) !== false || strpos($value['default_value'], $fieldName) !== false;
                    });

                    var_dump($datum); die();
                    if (count($filteredMapping) > 0) {
                        $columnsExistsInMapping[] = $fieldName;
                    }
                }
            }

            foreach ($dataMappings as $dataMapping) {
                var_dump($dataMapping['field']); die();
            }
        }
    }

    public function generateCustomizedMappingExcelFile($forSyncDatum, $localDiskName, $fieldMappingDetails)
    {

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
