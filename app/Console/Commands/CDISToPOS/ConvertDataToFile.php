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
use App\Traits\JobCancellationTrait;
use App\Traits\PusherTrait;
use App\Traits\StorageTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ConvertDataToFile extends Command
{
    use DatabaseTransaction, GenericHelper, PusherTrait, JobCancellationTrait, StorageTrait;

    public $extension = 'csv';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:convert-data-to-file {--interval=true}{--limit=true}{--broadcast=false}{--progress=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert CDIS data to specific file';

    public $broadcast = false;
    private $mappingVariable = [];
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
        $timeStart = microtime(true);

        ini_set('max_execution_time', '-1');
        ini_set('memory_limit', '-1');

        $branchCode = config('configuration.branch_code');

        $interval = $this->option('interval');
        $interval =
            filter_var($interval, FILTER_VALIDATE_BOOLEAN)
            ? config('sync.cdis.to_catapult.interval')
            : ((int) $interval
                ? filter_var($interval, FILTER_VALIDATE_INT)
                : false
            );

        $limit = $this->option('limit');
        $limit =
            filter_var($limit, FILTER_VALIDATE_BOOLEAN)
            ? config('sync.cdis.to_catapult.limit')
            : ((int) $limit
                ? filter_var($limit, FILTER_VALIDATE_INT)
                : false
            );

        $broadcast = $this->option('broadcast');
        $broadcast = filter_var($broadcast, FILTER_VALIDATE_BOOLEAN);

        $showProgress = $this->option('progress');
        $showProgress = filter_var($showProgress, FILTER_VALIDATE_BOOLEAN);

        Cache::forget('excludedEntries');
        Cache::forget('excludedSyncBids');

        $this->createLog(
            __('info.conversion_started'),
            'info',
            true
        );

        // Conversion already been executed
        $this->setConverting();

        if ($broadcast) {
            $this->initializePusher();
            $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'Converting', __('info.converting'), null);
        }

        $syncEntries = app()->make(SyncEntryRepository::class)
            ->list((object) array('type' => MappingType::CDIS_TO_POS));

        foreach ($syncEntries as $syncEntry) {
            $this->syncEntries[$syncEntry->name] = $syncEntry->alias;
            Cache::forget('file_storage_setup_'.$syncEntry->name);
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

        $hasBeenCancelled = $this->hasBeenCancelledConversion();;

        while (true && ! $hasBeenCancelled) {
            $timeStamp = Carbon::now()->format('mdY_His_v');
            $folderName = Carbon::now()->format('Ymd_His');

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

            if ($limit) {
                $forSyncData = $forSyncData->limit($limit);
            }

            $forSyncData = $forSyncData->get();

            if ($forSyncData->count() <= 0) {
                foreach ($excludedEntries as $excludedEntry) {
                    $this->createLog(
                        __('error.no_field_mapping_detected'),
                        'warn',
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

                if (is_int($interval)) {
                    sleep($interval);
                } else {
                    break;
                }

                continue;
            }

            $excelDataCollection = [];
            $progress = 0;
            $totalCount = count($forSyncData);

            foreach ($forSyncData as $forSyncDatum) {
                $hasBeenCancelled = $this->hasBeenCancelledConversion();
                if ($hasBeenCancelled) {
                    break;
                }
                $progress++;
                $targetFolder = '/'.$forSyncDatum->branch_bid.'/'.$folderName;
                $this->createLog(__('info.processing').$forSyncDatum->table_name, 'info', true, [$progress.'/'.$totalCount],);
                $this->processCustomizedMapping($forSyncDatum, $fieldMappingDetails, $timeStamp, $targetFolder,  $excelDataCollection);

                if ($broadcast &&  $showProgress) {
                    $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'Converting', __('info.converting').$progress.'/'.$totalCount, null);
                } else {
                    if ($broadcast && ($progress % 100 == 0)) {
                        $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'Converting', __('info.converting').$progress.'/'.$totalCount, null);
                    }
                }
            }

            $progress = 0;
            $totalCount = count($excelDataCollection);
            foreach ($excelDataCollection as $filePath => $detail) {
                $hasBeenCancelled = $this->hasBeenCancelledConversion();
                if ($hasBeenCancelled) {
                    break;
                }
                Excel::store(
                    new DataConversionToExcel($detail['headers'], $detail['data'], $this->extension),
                    $filePath,
                    $detail['disk_name']
                );

                $progress++;
                $this->createLog(__('info.creating_file').$filePath, 'info', true, [$progress.'/'.$totalCount]);

                if ($broadcast && $showProgress) {
                    $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'Converting', __('info.creating_file').$progress.'/'.$totalCount, null);
                }
            }

            if (is_int($interval)) {
                sleep($interval);
            } else {
                break;
            }
            if ($hasBeenCancelled) {
                break;
            }
        }

        $timeEnd = microtime(true);
        $executionTime = ($timeEnd - $timeStart);

        if ($hasBeenCancelled) {
            $this->clearCancelledConversion();
            $this->createLog(__('info.create_csv_for_new_branch_cancelled').' @ '.$this->secondsToHumanReadableTime($executionTime));
        } else {
            $this->createLog(__('info.create_csv_for_new_branch_success').' @ '.$this->secondsToHumanReadableTime($executionTime));
        }

        if ($broadcast) {
            if ($hasBeenCancelled) {
                $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'ConversionDone', __('info.create_csv_for_new_branch_cancelled').' @ '.$this->secondsToHumanReadableTime($executionTime), null);
            } else {
                $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'ConversionDone',  __('info.create_csv_for_new_branch_success').' @ '.$this->secondsToHumanReadableTime($executionTime), null);
            }
        }
        $this->clearCancelledConversion();
        $this->clearConverting();
    }

    /**
     * Process non-customized mapping conversion of CDIS data to excel file
     *
     * @param string  $entryName
     * @param object  $forSyncDatum
     * @param object  $timeStamp
     *
     * @return mixed
     */
    public function processNonCustomizedMapping($entryName, $forSyncDatum, $timeStamp)
    {
        $remoteDiskName = '';
        $localDiskName = '';

        $filters = (object) [
            'data_entry' => $entryName,
            'status' => Status::ACTIVE,
            'type' => 1,
        ];

        $fieldMappingDetails = Cache::remember('file_storage_setup_'.$entryName, 60*60, function () use($filters) {
            return app()
                ->make(FieldMappingRepository::class)
                ->list($filters, false, ['fileStorageSetup']);
        });

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
            $this->processNonCustomizedMappingConversion($forSyncDatum, $localDiskName, $fileStorageSetup, $timeStamp);
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
    public function processNonCustomizedMappingConversion($forSyncDatum, $localDiskName, $fileStorageSetup, $timeStamp)
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

            $result = $this->generateNonCustomizedMappingGroupedExcelFile($toSyncData, $forSyncDatum, $action, $localDiskName, $groupedEntrySymbol, $timeStamp);
        } else {
            $entrySymbol = $this->getSyncEntryAlias($forSyncDatum->table_name);

            if (is_null($entrySymbol)) {
                $this->cacheSetOfValue('excludedSyncBids', $forSyncDatum->bid);
                return false;
            }

            $result = $this->generateNonCustomizedMappingExcelFile($forSyncDatum, $action, $localDiskName, $entrySymbol, $timeStamp);
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
    public function generateNonCustomizedMappingExcelFile($forSyncDatum, $action, $localDiskName, $entrySymbol, $timeStamp)
    {
        return $this->transaction(function() use ($forSyncDatum, $action, $localDiskName, $entrySymbol, $timeStamp) {
            $result = $this->nonCustomizedMappingData([$forSyncDatum], $forSyncDatum);

            if ($result) {
                $fileName = $action.$entrySymbol.'_'.$timeStamp;
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
    public function generateNonCustomizedMappingGroupedExcelFile($toSyncData, $forSyncDatum, $action, $localDiskName, $groupedEntrySymbol, $timeStamp)
    {
        return $this->transaction(function() use($toSyncData, $forSyncDatum, $action, $localDiskName, $groupedEntrySymbol, $timeStamp) {
            $result = $this->nonCustomizedMappingData($toSyncData, $forSyncDatum);

            if ($result) {
                try {
                    $groupEntrySymbol = $groupedEntrySymbol;
                    $folderName = $action.$groupEntrySymbol.'_'.$timeStamp;

                    $this->createLog($folderName .' created', 'info', true, [], [$forSyncDatum->table_name]);

                    foreach ($result->headers as $key => $header) {
                        $tableName = $toSyncData[$key]->table_name;
                        $level = $toSyncData[$key]->level;
                        $entrySymbol = $this->getSyncEntryAlias($tableName);
                        $fileName = $action.$entrySymbol.'_'.$timeStamp;

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
     * @param object  $fieldMappingDetails
     *
     * @return mixed
     */
    public function processCustomizedMapping($forSyncDatum, $fieldMappingDetails, $timeStamp, $targetFolder, &$excelDataCollection)
    {
        return $this->transaction(function() use($forSyncDatum, $fieldMappingDetails, $timeStamp, $targetFolder, &$excelDataCollection) {
            if ($forSyncDatum) {
                if ($forSyncDatum->group) {
                    $toSyncData = CDISSync::where([
                        'branch_bid' => $forSyncDatum->branch_bid,
                        'group' => $forSyncDatum->group,
                        'code' => $forSyncDatum->code,
                    ])->get();

                    $result = $this->generateCustomizedMappingGroupedExcelFile($toSyncData, $forSyncDatum, $fieldMappingDetails, $timeStamp, $targetFolder, $excelDataCollection);

                    if ($result) {
                        CDISSync::whereIn('bid', $toSyncData->pluck('bid'))->delete();
                    }

                    return $result;
                } else {
                    $result = $this->generateCustomizedMappingExcelFile($forSyncDatum, $fieldMappingDetails, $timeStamp, $targetFolder, $excelDataCollection);

                    if ($result) {
                        CDISSync::where([
                            'table_name' => $forSyncDatum->table_name,
                            'table_bid' => $forSyncDatum->table_bid,
                        ])->delete();
                    }

                    return $result;
                }
            } else {
                $this->info(__('message.no_data_to_convert_to_file'));

                return false;
            }
        });
    }

    public function generateCustomizedMappingGroupedExcelFile($toSyncData, $forSyncDatum, $fieldMappingDetails, $timeStamp, $targetFolder, &$excelDataCollection)
    {
        foreach ($fieldMappingDetails as $fieldMappingDetail) {
            $primaryTable = $fieldMappingDetail->primary_table;
            $entryName = $fieldMappingDetail->data_entry;
            $primaryColumnName = $fieldMappingDetail->dataMappings->where('is_primary_key', 1)->first()['column_name'];

            if (is_null($primaryColumnName)) {
                $this->createLog('Primary key not found. Please contact administrator',
                    'error',
                    true,
                    [],
                    [$entryName]
                );

                continue;
            }

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

            $this->checkDirectory($remoteDiskName, $targetFolder);

            foreach ($toSyncData as $syncEntry) {
                $entityName = $syncEntry->table_name;

                $mappingFound = array_values(array_filter($dataMappings, function($value) use($entityName, $entryName, $toSyncData, $primaryTable) {
                    return preg_match('%\b('.$entityName.'.)\b%i', $value['field'])
                        || preg_match('%\b('.$entityName.'.)\b%i', $value['default_value']);
                }));

                if (count($mappingFound) == 0) {
                    continue;
                }

                $entityName = str_replace('_', '', Str::title($syncEntry->table_name));
                $entity = "App\\Entities\\CDIS".$entityName;

                $hasSoftDeleting = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($entity));

                $entryData = $entity::where('bid', $syncEntry->table_bid);

                if ($hasSoftDeleting) {
                    $entryData = $entryData->withTrashed();
                }

                $entryData = $entryData->first();

                $entryTableName = str_replace('cdis_', '', $entryData->tableName());

                $mappedData = [];
                if ($primaryTable == $entryTableName) {
                    $mappedData[] = $this->plotMapping($dataMappings, $entryData, $entryTableName, $syncEntry, $entryName);
                } else {
                    $referenceFound = explode('.', $mappingFound[0]['field']);

                    unset($referenceFound[count($referenceFound) - 1]);

                    $entryIndexInFoundMapping = array_search($entryTableName, $referenceFound);
                    foreach ($referenceFound as $key => $datum) {
                        if ($key >= $entryIndexInFoundMapping) {
                            unset($referenceFound[$key]);
                        }
                    }

                    $relationCamelCase = array_map(function($value) {
                        return Str::camel($value);
                    }, $referenceFound);

                    $referenceFoundRelation = implode('.', array_reverse($relationCamelCase));

                    $eagerLoadedData = $entryData->load($referenceFoundRelation);

                    $relationData = $eagerLoadedData;
                    foreach ($relationCamelCase as $function) {
                        if (! isset($relationData->{$function})) {
                            break;
                        }

                        $relationData = $relationData->{$function};
                    }

                    foreach ($relationData as $relationDatum) {
                        $tableName = str_replace('cdis_', '', $relationDatum->tableName());
                        $mappedData[] = $this->plotMapping($dataMappings, $relationDatum, $tableName, $syncEntry, $entryName);
                    }
                }

                foreach ($mappedData as $mappedDatum) {
                    $mappedHeaders = array_keys($mappedDatum);
                    $mappedValues = array_values($mappedDatum);

                    $filePath = $targetFolder.'/'.$entryName.'_'.$timeStamp.'.'.$this->extension;

                    if (! isset($excelDataCollection[$filePath])) {
                        $excelDataCollection[$filePath] = [
                            'headers' => $mappedHeaders,
                            'disk_name' => $localDiskName,
                            'data' => []
                        ];
                    }

                    if (isset($excelDataCollection[$filePath])) {
                        $foundHeader = $excelDataCollection[$filePath]['headers'];

                        $primaryColumnNameIndex = array_search($primaryColumnName, $foundHeader);

                        if ($primaryColumnNameIndex === false) {
                            continue;
                        }

                        $primaryColumnNameValue = $mappedValues[$primaryColumnNameIndex];

                        $rowIndex = array_search($primaryColumnNameValue, array_column($excelDataCollection[$filePath]['data'], $primaryColumnNameIndex));

                        if ($rowIndex !== false) {
                            $excelDataCollection[$filePath]['data'][$rowIndex] = $mappedValues;
                        } else {
                            $excelDataCollection[$filePath]['data'][] = $mappedValues;
                        }

                        $excelDataCollection[$filePath]['data'][] = $mappedValues;

                        $this->createLog(
                            $filePath .' updated',
                            'info',
                            true,
                            [],
                            ['Row: '.($rowIndex + 2).' | '.$primaryColumnName.': '.$primaryColumnNameValue]
                        );
                    } else {
                        $excelDataCollection[$filePath]['data'][] = $mappedValues;

                        $this->createLog(
                            $filePath .' created',
                            'info',
                            true,
                            []
                        );
                    }
                }
            }
        }

        return true;
    }

    public function plotMapping($dataMappings, $entryData, $entryTableName, $syncEntry, $entryName = '', $paramName = null, $paramData = null)
    {
        $data = [];
        $mappingField = 'None';
        $defaultValue = '';

        try {
            if (! is_null($paramName)) {
                ${$paramName} = $paramData;

                unset($dataMappings[0]);
            }

            foreach ($dataMappings as $dataMapping) {
                $mappingField = $dataMapping['field'];
                $defaultValue = $dataMapping['default_value'];

                $field = explode('.', $dataMapping['field']);

                if ($dataMapping['field'] && count($field) == 2) {
                    $fieldColumn = $field[1];
                    $data[$dataMapping['column_name']] = $entryData[$fieldColumn];
                } else if ($dataMapping['field'] && count($field) >= 3) {
                    if (str_starts_with($dataMapping['field'], $entryTableName.'.')) {
                        $data[$dataMapping['column_name']] =
                            $this->mappedSpecificData($dataMapping, $syncEntry, $entryTableName, $entryData, null, $paramName, $paramData);
                    }
                } else if (! $dataMapping['field'] && $dataMapping['default_value']) {
                    $defaultValueCondition = $dataMapping['default_value'];

                    preg_match_all("/\\[(.*?)\\]/", $defaultValueCondition, $matches);

                    if ($matches[0] || preg_match_all("/\\((.*?)\\)/", $defaultValueCondition, $matches)) {
                        $matchesColumns = array_unique($matches[1]);
                        $bracketedMatchesColumns = $matches[0];

                        foreach ($matchesColumns as $index => $matchesColumnString) {
                            $matchesColumnString = str_replace(' ', '', $matchesColumnString);

                            if (str_starts_with($matchesColumnString, $entryTableName.'.')) {
                                $matchesColumn = preg_split("/\.(?![^{]+\})/", $matchesColumnString);

                                if (count($matchesColumn) >= 2) {

                                    $conditionColumnValue = $this->mappedSpecificData(['field' => $matchesColumnString], $syncEntry, $entryTableName, $entryData, null, $paramName, $paramData);

                                    if (! is_null($conditionColumnValue)) {
                                        $conditionColumnValue = '"'.$conditionColumnValue.'"';
                                    }

                                    $defaultValueCondition =
                                        str_replace(
                                            $bracketedMatchesColumns[$index],
                                            $conditionColumnValue ?? 'NULL',
                                            $defaultValueCondition);
                                } else {
                                    $columnName = $matchesColumn[count($matchesColumn) - 1];
                                    $defaultValueCondition =
                                        str_replace(
                                            $bracketedMatchesColumns[$index],
                                            $entryData[$columnName] ?? 'NULL',
                                            $defaultValueCondition);
                                }
                            }
                        }

                        eval("\$defaultValueCondition = $defaultValueCondition;");
                    } else if ((strpos($defaultValueCondition, '$') !== false)) {
                        eval("\$defaultValueCondition = $defaultValueCondition;");
                    }

                    $data[$dataMapping['column_name']] = $defaultValueCondition;
                }

            }
        } catch (\Throwable $throwable) {
            $this->createLog(
                'Failed to create '. $this->extension. '. Please contact administrator',
                'error',
                true,
                []
            );

            $this->createLog($throwable->getMessage(). ' at line '. $throwable->getLine(), 'error', true, ['Mapping'], [$defaultValue, $mappingField, $entryName]);
        }

        return $data;
    }

    public function checkDataCondition($dataMapping, $syncEntry = null, $entryTableName = null, $entryData = null, $entryName = null)
    {
        $condition = $dataMapping['field'];

        preg_match_all("/\\[(.*?)\\]/", $condition, $matches);

        if ($matches[0] || preg_match_all("/\\((.*?)\\)/", $condition, $matches)) {
            $matchesColumns = array_unique($matches[1]);
            $bracketedMatchesColumns = $matches[0];

            foreach ($matchesColumns as $index => $matchesColumnString) {
                $matchesColumnString = str_replace(' ', '', $matchesColumnString);

                if (str_starts_with($matchesColumnString, $entryTableName.'.')) {
                    $matchesColumn = preg_split("/\.(?![^{]+\})/", $matchesColumnString);

                        if (count($matchesColumn) >= 2) {
                            $conditionColumnValue = $this->mappedSpecificData(['field' => $matchesColumnString], $syncEntry, $entryTableName, $entryData, $entryName);

                            if (! is_null($conditionColumnValue)) {
                                $conditionColumnValue = '"'.$conditionColumnValue.'"';
                            }

                            $condition =
                                str_replace(
                                    $bracketedMatchesColumns[$index],
                                    $conditionColumnValue ?? 'NULL',
                                    $condition);
                        } else {
                            $columnName = $matchesColumn[count($matchesColumn) - 1];
                            $condition =
                                str_replace(
                                    $bracketedMatchesColumns[$index],
                                    $entryData[$columnName] ?? 'NULL',
                                    $condition);
                        }
                }
            }
        }

        preg_match_all('/\\@(.*?)\\((.*?)\\)/', $condition, $functions);
        $functionConditions = $functions[0];
        $isPassed = false;

        foreach ($functionConditions as $functionCondition) {
            $value = null;
            $function = str_replace('@', '', $functionCondition);
            eval("\$value = $function;");
            $value = json_encode($value);
            $condition = str_replace($functionCondition, (string) $value, $condition);
        }

        eval("\$isPassed = $condition;");

        return $isPassed;
    }

    public function mappedSpecificData($dataMapping, $syncEntry = null, $entryTableName = null, $entryData = null, $entryName = null, $paramName = null, $paramData = null)
    {
        if (! is_null($paramName)) {
            ${$paramName} = $paramData;
        }

        if ((! is_null($entryTableName) || ! is_null($entryData) && ! is_null($syncEntry))) {
            $relationString = str_replace($entryTableName.'.', '', $dataMapping['field']);
            $relationArray = preg_split("/\.(?![^{]+\})/", $relationString);
            $columnName = $relationArray[count($relationArray) - 1];
            unset($relationArray[count($relationArray) - 1]);
            $relationData = $entryData;

            foreach ($relationArray as $entity) {
                $hasConditions = preg_match('/\{(.+)\}/', $entity) > 0;

                if ($hasConditions) {
                    preg_match_all('/\{(.+)\}/', $entity, $conditionFound);
                    if (count($conditionFound[0]) > 0) {
                        $conditions = $conditionFound[0][0];

                        preg_match_all("/sync\\((.*?)\\)/", $conditions, $syncBasisFound);

                        if (count($syncBasisFound[0]) > 0) {
                            foreach ($syncBasisFound[0] as $value) {
                                preg_match_all("/\\((.*?)\\)/", $value, $parameters);

                                if (count($parameters[0]) > 0) {
                                    $callableFunction = str_replace($parameters[0][0], '', $value);
                                    $parameters = explode(',', str_replace('"', '', $parameters[1][0]));

                                    foreach ($parameters as $index => $parameter) {
                                        if (strpos($parameter, '$') !== false) {
                                            eval("\$parameters[$index] = $parameter;");
                                        }
                                    }

                                    $syncReferenceValue = $this->{$callableFunction}(...$parameters);

                                    $conditions = str_replace($syncBasisFound[0][0], $syncReferenceValue, $conditions);
                                }
                            }
                        }

                        preg_match_all('/\{(.+)\}/', $conditions, $match);

                        if (count($match[0]) > 0) {
                            foreach ($match[0] as $value) {
                                if (preg_match_all('/\[(.+)\]/', $value, $conditionColumnReferencesMatches)) {
                                    if (count($conditionColumnReferencesMatches[0]) > 0) {
                                        $conditionColumnReferences = $conditionColumnReferencesMatches[1];
                                        $conditionColumnValue = $this->mappedSpecificData(
                                            $conditionColumnReferences[0],
                                            null,
                                            null,
                                            null,
                                            null,
                                            $paramName,
                                            $paramData);
                                        $conditions = str_replace($conditionColumnReferencesMatches[0][0], $conditionColumnValue, $conditions);
                                    }
                                }
                            }
                        }
                    }

                    $conditions = json_decode($conditions, true);

                    $function = Str::camel(str_replace($conditionFound[0][0], '', $entity));

                    if (! is_null($relationData)) {
                        $relationData = $relationData->{$function}();

                        if (! is_null($conditions)) {
                            foreach ($conditions as $key => $value) {
                                if ($value === 'NULL') {
                                    $value = NULL;
                                } else if (strpos($value, '$') !== false) {
                                    eval("\${'value'} = $value;");
                                }

                                $relationData = $relationData->where($key, $value);
                            }
                        }

                        $relationData = $relationData->first();
                    }
                } else {
                    $function = Str::camel($entity);
                    if (! isset($relationData->{$function})) {
                        return null;
                    }

                    $relationData = $relationData->{$function};
                }
            }
        } else {
            $relationArray = preg_split("/\.(?![^{]+\})/", $dataMapping);

            $columnName = $relationArray[count($relationArray) - 1];
            unset($relationArray[count($relationArray) - 1]);

            $relationString = $relationArray[0];
            preg_match_all('/\{(.+)\}/', $relationString, $conditionFound);
            $conditions = $conditionFound[0][0];
            $entityName = str_replace($conditions, '', $relationString);

            $entityName = str_replace('_', '', Str::title($entityName));
            $entity = "App\\Entities\\CDIS".$entityName;

            $hasSoftDeleting = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($entity));
            $conditions = json_decode($conditions, true);

            $relationData = $entity;
            foreach ($conditions as $key => $value) {
                if (array_key_first($conditions) == $key) {
                    $relationData = $relationData::where($key, $value);
                } else {
                    $relationData = $relationData->where($key, $value);
                }
            }

            if ($hasSoftDeleting) {
                $relationData = $relationData->withTrashed();
            }

            $relationData = $relationData->first();
        }

        if ($relationData) {
            $datum = null;

            if (isset($relationData->{$columnName})) {
                return $relationData->{$columnName};
            } else {
                $relationData = $relationData->toArray();

                $data = array_filter(collect($relationData)->pluck($columnName)->toArray());

                if (count($data) == 1) {
                    $datum = array_values($data)[0];
                } else if (count($data) >= 2) {
                    $datum = implode(',', $data);
                }

                return $datum;
            }
        } else {
            return null;
        }
    }

    public function sync($columnName = 'bid', $syncEntry)
    {
        return (string) $syncEntry[$columnName];
    }

    public function generateCustomizedMappingExcelFile($forSyncDatum, $fieldMappingDetails, $timeStamp, $targetFolder, &$excelDataCollection)
    {
        $noPrimaryKeyDetected = true;

        foreach ($fieldMappingDetails as $fieldMappingDetail) {
            $primaryTable = $fieldMappingDetail->primary_table;
            $entryName = $fieldMappingDetail->data_entry;
            $condition = $fieldMappingDetail->data_condition;

            if ($primaryTable !== $forSyncDatum->table_name) {
                continue;
            }

            $primaryColumnName = $fieldMappingDetail->dataMappings->where('is_primary_key', 1)->first()['column_name'];

            if (is_null($primaryColumnName)) {
                $this->createLog('Primary key not found. Please contact administrator',
                    'error',
                    true,
                    [],
                    [$entryName]
                );

                if ($noPrimaryKeyDetected) {
                    $noPrimaryKeyDetected = false;
                }

                continue;
            }

            $fileStorageSetup = $fieldMappingDetail->fileStorageSetup;
            $dataMappings = $fieldMappingDetail->dataMappings->toArray();
            $variableDefaultValue = $dataMappings[0]['default_value'];
            $hasVariables = (strpos($variableDefaultValue, '=>') !== false);

            if ($hasVariables) {
                preg_match_all("/\\((.*?)\\)/", $variableDefaultValue, $parameters);

                if (count($parameters[0]) > 0) {
                    $paramName = ltrim($parameters[1][0], '$');
                    preg_match_all('/\{(.+)\}/', $variableDefaultValue, $entityDataSource);

                    if (count($entityDataSource[0]) > 0) {
                        $GLOBALS[$paramName.'_source'] = Cache::remember($paramName.'_source', 300, function () use($entityDataSource) {
                            $entity = "\\App\\Entities\\".$entityDataSource[1][0];
                            eval("\${'entityData'} = $entity;");

                            return [
                                'type' => 'foreach',
                                'data' => ${'entityData'}
                            ];
                        });
                    } else {
                        $GLOBALS[$paramName.'_source'] = Cache::remember($paramName.'_source', 300, function () use($entityDataSource, $variableDefaultValue) {
                            $variableDefaultValue = explode('=>', $variableDefaultValue);
                            $entity = "\\App\\Entities\\".trim($variableDefaultValue[1]);
                            eval("\${'entityData'} = $entity;");

                            return [
                                'type' => 'param',
                                'data' => ${'entityData'}
                            ];
                        });
                    }
                }
            }

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

            $entityName = $forSyncDatum->table_name;

            $this->checkDirectory($remoteDiskName, $targetFolder);

            $mappingFound = array_values(array_filter($dataMappings, function($value) use($entityName) {
                return preg_match('%\b('.$entityName.'.)\b%i', $value['field'])
                    || preg_match('%\b('.$entityName.'.)\b%i', $value['default_value']);
            }));

            if (count($mappingFound) > 0) {
                $entityName = str_replace('_', '', Str::title($forSyncDatum->table_name));
                $entity = "App\\Entities\\CDIS".$entityName;

                $hasSoftDeleting = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($entity));

                $entryData = $entity::where('bid', $forSyncDatum->table_bid);

                if ($hasSoftDeleting) {
                    $entryData = $entryData->withTrashed();
                }

                $entryData = $entryData->first();

                if (is_null($entryData)) {
                    $this->createLog(
                        'Missing data. Please contact administrator',
                        'error',
                        true,
                        [$forSyncDatum->table_bid],
                        [$forSyncDatum->table_name]
                    );
                    continue;
                }

                if (! is_null($condition) && $condition !== '') {
                    $isConditionPassed = $this->checkDataCondition(['field' => $condition], $forSyncDatum, $primaryTable, $entryData, $entryName);

                    if (! $isConditionPassed) {
                        continue;
                    }
                }

                $entryTableName = str_replace('cdis_', '', $entryData->tableName());

                $mappedData = [];

                $forEachVariableData = [null];
                $paramName = null;

                if ($hasVariables) {
                    preg_match_all("/\\((.*?)\\)/", $variableDefaultValue, $parameters);

                    if (count($parameters[0]) > 0) {
                        $paramName = ltrim($parameters[1][0], '$');
                        if ($GLOBALS[$paramName.'_source']['type'] === 'foreach') {
                            $forEachVariableData = $GLOBALS[$paramName.'_source']['data'];
                        }
                    }
                }

                if ($primaryTable == $entryTableName) {
                    foreach ($forEachVariableData as $paramData) {
                        $mappedData[] = $this->plotMapping($dataMappings, $entryData, $entryTableName, $forSyncDatum, $entryName, $paramName, $paramData);
                    }
                } else {
                    $referenceFound = explode('.', $mappingFound[0]['field']);
                    unset($referenceFound[count($referenceFound) - 1]);

                    $entryIndexInFoundMapping = array_search($entryTableName, $referenceFound);
                    foreach ($referenceFound as $key => $datum) {
                        if ($key >= $entryIndexInFoundMapping) {
                            unset($referenceFound[$key]);
                        }
                    }

                    $relationCamelCase = array_map(function($value) {
                        return Str::camel($value);
                    }, $referenceFound);

                    $referenceFoundRelation = implode('.', array_reverse($relationCamelCase));

                    $eagerLoadedData = $entryData->load($referenceFoundRelation);

                    $relationData = $eagerLoadedData;
                    foreach ($relationCamelCase as $function) {
                        $relationData = $relationData->{$function};
                    }

                    foreach ($relationData as $relationDatum) {
                        $tableName = str_replace('cdis_', '', $relationDatum->tableName());
                        foreach ($forEachVariableData as $paramData) {
                            $mappedData[] = $this->plotMapping($dataMappings, $relationDatum, $tableName, $forSyncDatum, $entryName, $paramName, $paramData);
                        }
                    }
                }

                foreach ($mappedData as $mappedDatum) {
                    $mappedHeaders = array_keys($mappedDatum);
                    $mappedValues = array_values($mappedDatum);

                    $filePath = $targetFolder.'/'.$entryName.'_'.$timeStamp.'.'.$this->extension;

                    if (! isset($excelDataCollection[$filePath])) {
                        $excelDataCollection[$filePath] = [
                            'headers' => $mappedHeaders,
                            'disk_name' => $localDiskName,
                            'data' => []
                        ];
                    }

                    $localDisk = Storage::disk($localDiskName);

                    if (isset($excelDataCollection[$filePath])) {
                        $foundHeader = $excelDataCollection[$filePath]['headers'];

                        $primaryColumnNameIndex = array_search($primaryColumnName, $foundHeader);

                        if ($primaryColumnNameIndex === false) {
                            continue;
                        }

                        $primaryColumnNameValue = $mappedValues[$primaryColumnNameIndex];

                        $rowIndex = array_search($primaryColumnNameValue, array_column($excelDataCollection[$filePath]['data'], $primaryColumnNameIndex));

                        if ($rowIndex !== false) {
                            $excelDataCollection[$filePath]['data'][$rowIndex] = $mappedValues;
                        } else {
                            $excelDataCollection[$filePath]['data'][] = $mappedValues;
                        }

                        $this->createLog(
                            $filePath .' updated',
                            'info',
                            true,
                            [],
                            ['Row: '.($rowIndex + 2).' | '.$primaryColumnName.': '.$primaryColumnNameValue]
                        );
                    } else {
                        $excelDataCollection[$filePath]['data'][] = $mappedValues;

                        $this->createLog(
                            $filePath .' created',
                            'info',
                            true,
                            []
                        );
                    }
                }
            }
        }

        return true;
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
