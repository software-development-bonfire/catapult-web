<?php

namespace App\Console\Commands\CDISToPOS;

use App\Entities\CDISSync;
use App\Entities\EntryCounter;
use App\Entities\ErrorLog;
use App\Entities\ErrorLogDetail;
use App\Entities\FieldMappingList;
use App\Entities\RemoteSetup;
use App\Enums\ApiEndpoint;
use App\Enums\Disk;
use App\Enums\EntryLevel;
use App\Enums\MappingType;
use App\Enums\Status;
use App\Services\CdisToCsvFileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use stdClass;

class CdisToCsvFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:CdisToCsvFile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change Json file to CSV file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CdisToCsvFileService $cdisToCsvFileService)
    {
        parent::__construct();
        $this->cdisToCsvFileService = $cdisToCsvFileService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        while (true) {
            $this->line('Syncing started..');
            
            $remote_setup = RemoteSetup::where('status', Status::ACTIVE)->first();
            
            if ($remote_setup) {
                resolve('filesystem')->forgetDisk(Disk::FTP_POST_TO_CDIS);
                app()['config']->set('filesystems.disks.'.Disk::FTP_POST_TO_CDIS.'.host', $remote_setup->host);
                app()['config']->set('filesystems.disks.'.Disk::FTP_POST_TO_CDIS.'.username', $remote_setup->username);
                app()['config']->set('filesystems.disks.'.Disk::FTP_POST_TO_CDIS.'.password', $remote_setup->password);
                app()['config']->set('filesystems.disks.'.Disk::FTP_POST_TO_CDIS.'.port', $remote_setup->port);
                app()['config']->set('filesystems.disks.'.Disk::FTP_POST_TO_CDIS.'.root', $remote_setup->path);
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
    
            $disk = Disk::FTP_POST_TO_CDIS;
    
            $endpoints = [
                [
                    'name' => ApiEndpoint::PRODUCT,
                    'table' => 'product',
                    'abv' => 'PR_',
                    'ftp_path' => '/CDIS to POS/Product/',
                ],
                [
                    'name' => ApiEndpoint::BRAND,
                    'table' => 'brand',
                    'abv' => 'BD_',
                    'ftp_path' => '/CDIS to POS/Brand/',
                ],
                [
                    'name' => ApiEndpoint::CATEGORY,
                    'table' => 'product_category',
                    'abv' => 'CT_',
                    'ftp_path' => '/CDIS to POS/Category/',
                ],
                [
                    'name' => ApiEndpoint::UOM,
                    'table' => 'unit_of_measurement',
                    'abv' => 'UOM_',
                    'ftp_path' => '/CDIS to POS/UOM/',
                ],
                [
                    'name' => ApiEndpoint::VENDOR,
                    'table' => 'vendor',
                    'abv' => 'VN_',
                    'ftp_path' => '/CDIS to POS/Vendor/',
                ],
                [
                    'name' => ApiEndpoint::PRODUCT_STRUCTURE,
                    'table' => 'product_structure',
                    'abv' => 'PS_',
                    'ftp_path' => '/CDIS to POS/Product Structure/',
                ],
                [
                    'name' => ApiEndpoint::PRODUCT_PRICING_TYPE,
                    'table' => 'product_pricing_type',
                    'abv' => 'PS_',
                    'ftp_path' => '/CDIS to POS/Product Pricing Type/',
                ],
                [
                    'name' => ApiEndpoint::PRODUCT_UOM_PACKAGING,
                    'table' => 'product_uom_packaging',
                    'abv' => 'PUP_',
                    'ftp_path' => '/CDIS to POS/Product/Product UOM Packaging/',
                ]
            ];
    
            $cdisSync = CDISSync::first();

            if ($cdisSync) {
                $endpointMap = [];
                foreach ($endpoints as $key => $endpoint) {
                    foreach ($endpoint as $k => $val) {
                        if ($k == 'table' && $cdisSync->table_name == $val) {
                            $endpointMap[] = $endpoints[$key];
                        }
                    }
                }
                $this->process($endpointMap[0], $cdisSync, $disk);
            } else {
                $this->info(Lang::get('message.no_data_to_sync'));
            }
            
            sleep(5);
        }
    }

    /**
     * @param  array   $endpoint
     * @param  object  $cdisSync
     * @param  object  $disk
     *
     * @return mixed
     */
    public function process($endpoint, $cdisSync, $disk)
    {
        $action = $cdisSync->action == 'create' ? 'C_'
            : ($cdisSync->action == 'update' ? 'U_' : 'D_');

        if ($cdisSync->group) {
            $cdisData = CDISSync::where([
                'branch_bid' => $cdisSync->branch_bid,
                'group' => $cdisSync->group,
                'code' => $cdisSync->code,
                'action' => $cdisSync->action])
                ->get();
            
            $result = $this->withGroup($cdisData, $endpoint, $cdisSync, $action, $disk);
        } else {
            $cdisData = CDISSync::where([
                'branch_bid' => $cdisSync->branch_bid,
                'table_name' => $cdisSync->table_name,
                'action' => $cdisSync->action,
                'level' => 1])
                ->get();

            $result = $this->withoutGroup($cdisData, $endpoint);

            $mapped = [];
    
            if ($result) {
                foreach ($result->value as $index => $arrayData) {
                    $dataMap = [];
    
                    foreach($arrayData as $tableColumn => $tableData) {
                        $exist = array_key_exists($tableColumn, $result->data_map);
                        if ($exist) {
                            $dataMap[] = $tableData;
                        }
                    }
    
                    $mapped[] = $dataMap;
                }
                
                $header = [];
    
                foreach ($result->data_map as $cdisHeader => $posHeader) {
                    $header[] = $posHeader;
                }
            }
    
            if(! $mapped || ! $header) {
                $this->warn(Lang::get('error.no_field_mapping_detected'));
                $this->createError($endpoint);
                return false;
            }

            $entryCounter = EntryCounter::where('mapping_type', MappingType::CDIS_TO_POS)
                ->whereDate('created_at', DB::raw('CURDATE()'))
                ->orderBy('created_at', 'DESC')
                ->first();
    
            $counter = $entryCounter ? $entryCounter->counter+1 : 1;
    
            $isConverted = $this->cdisToCsvFileService->saveToFTP($counter, $header, $mapped, $cdisSync, $endpoint, $action, $disk);
            if ($isConverted === true) {
                $this->info(Lang::get('message.conversion_successful'));
                foreach ($cdisData as $sync) {
                    CDISSync::find($sync->bid)->delete();
                }
            } else if ($isConverted === "limit") {
                $this->warn(Lang::get('error.entry_has_reach_the_limit'));
            } else {
                $this->warn(Lang::get('error.conversion_failed'));
            }
        }
    }

    /**
     * @param  object  $endpoint
     * @param  mixed   $cdisData
     *
     * @return mixed
     */
    public function withoutGroup($cdisData, $endpoint)
    {
        $value = [];

        foreach ((array) json_decode($cdisData) as $key => $sync) {
            $entityName = str_replace('_', '', Str::title($sync->table_name));
            $entity = "App\\Entities\\CDIS".$entityName;
            $dataTable = $entity::withTrashed()->where('bid', $sync->table_bid)->first();
            $fieldMapping = FieldMappingList::with('dataMappings')
                ->where([
                    'type' => MappingType::CDIS_TO_POS,
                    'api_endpoint' => str_replace('_', ' ', Str::title($sync->table_name)),
                    'status' => Status::ACTIVE
                ])->first();

            $data_map = [];
            $validate = [];

            if ($fieldMapping) {
                foreach ($fieldMapping->dataMappings as $key => $data) {
                    array_push($data_map, [
                        $data->field => $data->column_name === '""' ? $data->field : $data->column_name,
                    ]);

                    array_push($validate, [
                        "field" => $data->column_name === '""' ? $data->field : $data->column_name,
                        "required" => $data->required === 1 ? "required" : "sometimes",
                        "data_type" => ''
                    ]);
                }

            } else {
                $this->warn(Lang::get('error.no_field_mapping_detected'));
                $this->createError($endpoint);
                return false;
            }

            if ($data_map) {
                $data_map = call_user_func_array("array_merge", $data_map);
            }

            foreach ($validate as $key => $valid) {
                $array_key = array_search($validate[$key]['field'], $data_map);
                $validate[$key]['field'] = $array_key;
            }

            if ($fieldMapping) {
                $val = [];
                foreach((array) json_decode($dataTable) as $a => $data) {
                    $val[$a] = $data;
                }
                $value[] = $val;
            }
        }

        $result = new stdClass;
        $result->value = $value;
        $result->data_map = $data_map;
        return $result;
    }
    
    /**
     * create CSV file.
     * 
     * @param  array   $cdisData
     * @param  array   $endpoint
     * @param  array   $cdisSync
     * @param  string  $action
     * @param  string  $disk
     */
    public function withGroup($cdisData, $endpoint, $cdisSync, $action, $disk)
    {
        if ($endpoint['name'] == ApiEndpoint::PRODUCT && count($cdisData) >= EntryLevel::PRODUCT) {
            $result = $this->dataMapWithGroup($cdisData, $endpoint, $cdisSync);
        } else if ($endpoint['name'] == ApiEndpoint::PRODUCT_STRUCTURE 
            && count($cdisData) >= EntryLevel::PRODUCT_STRUCTURE) {
            $result = $this->dataMapWithGroup($cdisData, $endpoint, $cdisSync);
        } else if (($endpoint['name'] == ApiEndpoint::VENDOR 
            || $endpoint['name'] == ApiEndpoint::VENDOR_BRANCH)
            && count($cdisData) >= EntryLevel::VENDOR) {
            $result = $this->dataMapWithGroup($cdisData, $endpoint, $cdisSync);
        } else if ($endpoint['name'] == ApiEndpoint::PRODUCT_UOM_PACKAGING
            && count($cdisData) == EntryLevel::PRODUCT_UOM_PACKAGING) {
            $result = $this->dataMapWithGroup($cdisData, $endpoint, $cdisSync);
        } else {
            return;
        }

        if ($result) {

            
            $header = [];

            if ($result) {
                foreach ($result->headers as $index => $arrayData) {
                    $dataMap = [];
                    foreach($result->value as $key => $tableData) {
                        if ($index === $key) {
                            foreach ($tableData as $in => $data) {
                                $rowData = [];
                                foreach ($data as $column => $value) {
                                    if (array_key_exists($column, $arrayData)) {
                                        $rowData[] = $value;
                                    }
                                }
                                $dataMap[] = $rowData;
                            }
                        }
                    }

                    $head= [];
                    foreach ($result->headers[$index] as $cdisHeader => $posHeader) {
                        $head[] = $posHeader;
                    }

                    $header[] = $head;
                    $mapped[] = $dataMap;
                }
            }

            $isConverted = $this->cdisToCsvFileService->saveToFTPGroup($header, $mapped, $cdisSync, $endpoint, $action, $disk, $cdisData, $result->tables);
            
            if ($isConverted === true) {
                $this->info(Lang::get('message.conversion_successful'));
            } else if ($isConverted === "limit") {
                $this->warn(Lang::get('error.entry_has_reach_the_limit'));
            } else if ($isConverted === false){
                $this->warn(Lang::get('error.conversion_failed'));
            }
        } else {
            return;
        }
    }

    /**
     * create CSV file.
     * 
     * @param  mixed  $cdisData
     * @param  array  $endpoint
     * @param  array  $cdisSync
     */
    public function dataMapWithGroup($cdisData, $endpoint, $cdisSync)
    {
        $tables = [];
        foreach(json_decode($cdisData) as $t => $val) {
            $tableName = $val->table_name;
            if (! in_array($tableName, $tables)) {
                $tables[] = $tableName;
            }
        }

        $value = []; $headers = []; $head = [];
        
        foreach ($tables as $table) {
            $dataValue = [];
            $array = $cdisData->where('table_name', $table);
            foreach (json_decode($array) as $key => $data) {
                $entityName = str_replace('_', '', Str::title($data->table_name));
                $apiEndpoint = str_replace('_', ' ', Str::title($data->table_name));
                $entity = "App\\Entities\\CDIS".$entityName;
                if ($entityName = 'VendorBranch') {
                    $dataTable = $entity::where('bid', $data->table_bid)->first();
                } else {
                    $dataTable = $entity::withTrashed()->where('bid', $data->table_bid)->first();
                }

                $apiEndpoint = $apiEndpoint == 'Product' ? 'Product Head' 
                    : ($apiEndpoint == 'Product Uom Packaging' ? 'Product UOM Packaging' : $apiEndpoint);

                $fieldMapping = FieldMappingList::with('dataMappings')
                    ->where([
                        'type' => MappingType::CDIS_TO_POS,
                        'api_endpoint' => $apiEndpoint,
                        'status' => Status::ACTIVE
                    ])->first();

                $data_map = []; $validate = [];

                if ($fieldMapping) {
                    foreach ($fieldMapping->dataMappings as $key => $data) {
                        array_push($data_map, [
                            $data->field => $data->column_name === '""' ? $data->field : $data->column_name,
                        ]);

                        array_push($validate, [
                            "field" => $data->column_name === '""' ? $data->field : $data->column_name,
                            "required" => $data->required === 1 ? "required" : "sometimes",
                            "data_type" => ''
                        ]);
                    }

                } else {
                    $this->warn(Lang::get('error.no_field_mapping_detected'));
                    $this->createError($endpoint);
                    return false;
                }
                
                
    
                foreach ($validate as $key => $valid) {
                    $array_key = array_search($validate[$key]['field'], $data_map);
                    $validate[$key]['field'] = $array_key;
                }
    
                if ($fieldMapping) {
                    $val = [];
                    foreach((array) json_decode($dataTable) as $a => $data) {
                        $val[$a] = $data;
                    }
                    $dataValue[] = $val;
                }
            }
            if ($data_map) {
                $headers = call_user_func_array("array_merge", $data_map);
                $head[] = $headers; 
            }
            $value[] = $dataValue;
        }

        $result = new stdClass;
        $result->value = $value;
        $result->headers = $head;
        $result->tables = $tables;

        return $result;
    }

    public function mapping()
    {

    }

    /**
     * create error resource.
     * 
     * @param  array  $endpoint
     */
    public function createError($endpoint)
    {
        $errorExist = ErrorLog::where(['pos_entry' => $endpoint['name'], 'filename' => 'N/A'])->first();
        if (! $errorExist) {
            $errorLog = ErrorLog::create([
                'pos_entry' => $endpoint['name'],
                'filename' => 'N/A',
                'status' => Lang::get('error.failed_conversion')
            ]);
            ErrorLogDetail::create([
                'error_log_bid' => $errorLog->bid,
                'sheet' => 'N/A',
                'error_type' => 'Invalid data',
                'description' => "No column found, Please add configuration in Field Mapping."
            ]);
        }
    }
}
