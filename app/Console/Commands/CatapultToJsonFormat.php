<?php

namespace App\Console\Commands;

use App\Entities\DataMapping;
use App\Entities\ErrorLog;
use App\Entities\ErrorLogDetail;
use App\Entities\FieldMappingList;
use App\Enums\Acronym;
use App\Enums\ApiEndpoint;
use App\Enums\Directory;
use App\Enums\Disk;
use App\Enums\FileNameIdentifier;
use App\Enums\MappingType;
use App\Enums\Status;
use App\Exports\PosToCdisExport;
use App\Http\Requests\PosToCdisValidation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\CatapultToJsonFormatService;
use stdClass;

class CatapultToJsonFormat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:CatapultToJsonFormat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change file contents into Json Format';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CatapultToJsonFormatService $catapultToJsonFormatService)
    {
        parent::__construct();
        $this->catapultToJsonFormatService = $catapultToJsonFormatService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        while (true) {
            $this->line("CSV to json conversion started..");
            $endpoints = [
                    [
                        'name' => ApiEndpoint::TRANSACTION,
                        'source_path' => Directory::FOR_CONVERSION_TRANSACTION_TO_CONVERT,
                        'processed' => Directory::FOR_CONVERSION_TRANSACTION_PROCESSED,
                        'failed' => Directory::FOR_CONVERSION_TRANSACTION_FAILED,
                        'converted' => Directory::CONVERTED_TRANSACTION_TO_SYNC
                    ],
                    [
                        'name' => ApiEndpoint::ZREAD,
                        'source_path' => Directory::FOR_CONVERSION_ZREAD_TO_CONVERT,
                        'processed' => Directory::FOR_CONVERSION_ZREAD_PROCESSED,
                        'failed' => Directory::FOR_CONVERSION_ZREAD_FAILED,
                        'converted' => Directory::CONVERTED_ZREAD_TO_SYNC
                    ],
                    [
                        'name' => ApiEndpoint::AUDIT_TRAIL,
                        'source_path' => Directory::FOR_CONVERSION_AUDIT_TRAIL_TO_CONVERT,
                        'processed' => Directory::FOR_CONVERSION_AUDIT_TRAIL_PROCESSED,
                        'failed' => Directory::FOR_CONVERSION_AUDIT_TRAIL_FAILED,
                        'converted' => Directory::CONVERTED_AUDIT_TRAIL_TO_SYNC
                    ],
                    [
                        'name' => ApiEndpoint::CASH_BREAKDOWN,
                        'source_path' => Directory::FOR_CONVERSION_CASH_BREAKDOWN_TO_CONVERT,
                        'processed' => Directory::FOR_CONVERSION_CASH_BREAKDOWN_PROCESSED,
                        'failed' => Directory::FOR_CONVERSION_CASH_BREAKDOWN_FAILED,
                        'converted' => Directory::CONVERTED_CASH_BREAKDOWN_TO_SYNC
                    ],
                    [
                        'name' => ApiEndpoint::CASH_DRAWER,
                        'source_path' => Directory::FOR_CONVERSION_CASH_DRAWER_TO_CONVERT,
                        'processed' => Directory::FOR_CONVERSION_CASH_DRAWER_PROCESSED,
                        'failed' => Directory::FOR_CONVERSION_CASH_DRAWER_FAILED,
                        'converted' => Directory::CONVERTED_CASH_DRAWER_TO_SYNC
                    ]
                ];

            foreach($endpoints as $endpoint) {
                resolve('filesystem')->forgetDisk(Disk::LOCAL_POS_TO_CDIS);
                app()['config']->set('filesystems.disks.'.Disk::LOCAL_POS_TO_CDIS.'.root', public_path($endpoint['source_path']));
                $localDisk = Storage::disk(Disk::LOCAL_POS_TO_CDIS);

                $directories = $localDisk->directories();

                $result = $this->process($directories, $localDisk, $endpoint);

                $TH = []; $TH_success = false;
                $TD = []; $TD_success = false;
                $PR = []; $PR_success = false;
                $PM = []; $PM_success = false;
                $PD = []; $PD_success = true;
                $AD = []; $AD_success = true;

                $ZCB = []; $ZCB_success = false;
                $ZCS = []; $ZCS_success = false;
                $ZH = []; $ZH_success = false;
                $ZTD = []; $ZTD_success = false;
                $ZRD = []; $ZRD_success = true;

                $AT = []; $AT_success = false;

                $CH = []; $CH_success = false;
                $CD = []; $CD_success = false;

                $DR = []; $DR_success = false;

                foreach($result as $key => $data) {
                    if(isset($data['data'][0]->filename_identifier)) {
                        foreach ($data['data'] as $value) {
                            if ($endpoint['name'] === ApiEndpoint::TRANSACTION) {
                                if ($value->filename_identifier == Acronym::TRANSACTION_HEAD) {
                                    $TH = $value->data; $TH_success = $value->success;
                                } else if ($value->filename_identifier == Acronym::TRANSACTION_DETAIL) {
                                    $TD = $value->data; $TD_success = $value->success;
                                } else if ($value->filename_identifier == Acronym::PRODUCT) {
                                    $PR = $value->data; $PR_success = $value->success;
                                } else if ($value->filename_identifier == Acronym::PAYMENT_METHOD) {
                                    $PM = $value->data; $PM_success = $value->success;
                                } else if ($value->filename_identifier == Acronym::PRODUCT_DISCOUNT) {
                                    $PD = $value->data; $PD_success = $value->success;
                                } else {
                                    $AD_success = $value->success;
                                    $AD = $value->data;
                                }
                            } else if ($endpoint['name'] === ApiEndpoint::ZREAD) {
                                if ($value->filename_identifier == Acronym::CASH_BREAKDOWN) {
                                    $ZCB = $value->data; $ZCB_success = $value->success;
                                } else if ($value->filename_identifier == Acronym::CASHIER_SUMMARY) {
                                    $ZCS = $value->data; $ZCS_success = $value->success;
                                } else if ($value->filename_identifier == Acronym::ZREAD_HEAD) {
                                    $ZH = $value->data; $ZH_success = $value->success;
                                } else if ($value->filename_identifier == Acronym::TENDER_DETAILS) {
                                    $ZTD = $value->data; $ZTD_success = $value->success;
                                } else {
                                    $ZRD = $value->data;
                                    $ZRD_success = $value->success;
                                }
                            } else if ($endpoint['name'] === ApiEndpoint::AUDIT_TRAIL) {
                                $AT = $value->data;
                                $AT_success = $value->success;
                            } else if ($endpoint['name'] === ApiEndpoint::CASH_BREAKDOWN) {
                                if ($value->filename_identifier == Acronym::CASH_BREACKDOWN_HEAD) {
                                    $CH = $value->data;
                                    $CH_success = $value->success;
                                } else {
                                    $CD = $value->data;
                                    $CD_success = $value->success;
                                }
                            } else {
                                $DR = $value->data;
                                $DR_success = $value->success;
                            }
                        }

                        if ($endpoint['name'] === ApiEndpoint::TRANSACTION) {
                            $value = $this->catapultToJsonFormatService->transaction($TH, $TD, $PR, $PM, $PD, $AD);
                        } else if ($endpoint['name'] === ApiEndpoint::ZREAD) {
                            $value = $this->catapultToJsonFormatService->zread($ZCB, $ZCS, $ZH, $ZRD, $ZTD);
                        } else if ($endpoint['name'] === ApiEndpoint::AUDIT_TRAIL) {
                            $value = $this->catapultToJsonFormatService->auditTrail($AT);
                        } else if ($endpoint['name'] === ApiEndpoint::CASH_BREAKDOWN) {
                            $value = $this->catapultToJsonFormatService->cashBreakdown($CH, $CD);
                        } else {
                            $value = $this->catapultToJsonFormatService->cashDrawer($DR);
                        }
                    }

                    if ($TH_success && $TD_success && $PR_success && $PM_success && $PD_success && $AD_success
                        || ($ZCB_success && $ZCS_success && $ZH_success && $ZTD_success && $ZRD_success)
                        || $AT_success
                        || ($CH_success && $CD_success)
                        || $DR_success) {

                        resolve('filesystem')->forgetDisk(Disk::LOCAL_POS_TO_CDIS);
                        app()['config']->set('filesystems.disks.'.Disk::LOCAL_POS_TO_CDIS.'.root', public_path());

                        $localDisk = Storage::disk(Disk::LOCAL_POS_TO_CDIS);

                        if ($localDisk->exists($endpoint['processed'].'/'.$data['directory'])) {
                            $localDisk->deleteDirectory($endpoint['processed'].'/'.$data['directory']);
                            $localDisk->move($endpoint['source_path'].'/'.$data['directory'],
                            $endpoint['processed'].'/'.$data['directory']);
                        } else {
                            $localDisk->move($endpoint['source_path'].'/'.$data['directory'],
                            $endpoint['processed'].'/'.$data['directory']);
                        }

                        resolve('filesystem')->forgetDisk(Disk::LOCAL_POS_TO_CDIS);
                        app()['config']->set('filesystems.disks.'.Disk::LOCAL_POS_TO_CDIS.'.root', public_path($endpoint['converted']));
                        Storage::disk(Disk::LOCAL_POS_TO_CDIS)->put($data['directory'].'.txt', json_encode($value));

                        $this->info(Lang::get('conversion_successful'));
                    }
                }
            }

            sleep(5);
        }
    }

    /**
     *
     * @param mixed $localDisk
     * @param mixed $endpoint
     * @param string $directory
     * @return mixed
     */
    public function process($directories, $localDisk, $endpoint)
    {
        $allData = array();
        foreach($directories as $directory) {
            $file_count = substr($directory, -1);

            $allFiles = $localDisk->files($directory);

            $data = [];

            if(count($allFiles) == $file_count) {
                foreach ($allFiles as $file) {
                    $filename = substr($file, strrpos($file, '/') + 1);

                    $extension = substr($file, strrpos($file, '.') + 1);

                    switch($extension) {
                    case 'csv':
                        $result = $this->ConvertToJson($filename, $directory, $endpoint);
                        $data[] = $result;
                        break;
                    case 'xlsx':
                        //xlsx
                        break;
                    default:
                        dd($extension);
                    }
                }
                $allData[] = ["data" => $data, "directory" => $directory];
            }
        }
        return $allData;
    }

    /**
     * Convert File into Json format.
     *
     * @param string $filename
     * @param string $directory
     * @return mixed
     */
    public function ConvertToJson($filename, $directory, $endpoint)
    {
        $contents = Excel::toArray(new PosToCdisExport, public_path($endpoint['source_path'].'/'.$directory.'/'.$filename));

        $pos_data = array();

        $data = $contents[0];

        $indexes = [];
        foreach($data as $key => $c) {
            if($key === 0) {
                $indexes = $c;
            } else {
                $array_data = [];
                foreach($c as $a => $b) {
                    $array_data[$indexes[$a]] = $b;
                }
                $pos_data[] = $array_data;
            }
        }

        return $this->validation($pos_data, $filename, $endpoint, $directory);
    }

    /**
     * Matching of POS fields to CDIS fields.
     *
     * @param array $pos_data
     * @param string $filename
     * @param array $endpoint
     * @param array $directory
     * @return mixed
     */
    public function validation($pos_data, $filename, $endpoint, $directory)
    {   
        $filename_identifier = strtok($filename, '_');

        $field_mapping_list = FieldMappingList::with(['dataMappings' => function ($query) use ($filename_identifier){
            $query->where('file_name', $filename_identifier);
        }])
            ->where([
                'type' => MappingType::POS_TO_CDIS,
                'status' => Status::ACTIVE,
                'api_endpoint' => $endpoint['name']
            ])->first();

        $validate = [];

        $data_map = [];

        if ($field_mapping_list) {
            foreach ($field_mapping_list->dataMappings as $key => $data) {
                array_push($data_map, [
                    $data->field => $data->column_name === '""' ? $data->field : $data->column_name,
                ]);
    
                array_push($validate, [
                    "field" => $data->column_name === '""' ? $data->field : $data->column_name,
                    "required" => $data->required === 1 ? "required" : "sometimes",
                    "data_type" => $data->mapping_type == "VARCHAR" ? ""
                        :( $data->mapping_type == "DATE" ? "|string" 
                        :( $data->mapping_type == "DATETIME" ? "|string" 
                        :( $data->mapping_type == "TIME" ? "|string" 
                        :( $data->mapping_type == "TEXT" ? "|string|max:1024" 
                        :( $data->mapping_type == "DECIMAL" ? "|numeric|between:-99999999999999999999999.999999,99999999999999999999999.999999"
                        :  "|numeric" )))))
                ]);
            }
            if ($data_map) {
                $data_map = call_user_func_array("array_merge", $data_map);
            } else {
                $error_log = ErrorLog::create([
                    'pos_entry' => $endpoint['name'],
                    'filename' => $filename,
                    'status' => 'Failed conversion'
                ]);
                ErrorLogDetail::create([
                    'error_log_bid' => $error_log->bid,
                    'sheet' => $this->fileNameIdentifier($filename_identifier),
                    'error_type' => 'Invalid value',
                    'description' => "No column found, Please add configuration in Field Mapping."
                ]);
            }
    
            foreach ($validate as $key => $value) {
                $array_key = array_search($validate[$key]['field'], $data_map);
                $validate[$key]['field'] = $array_key;
            }
    
            $mapped = $this->dataMapped($pos_data, $data_map, $field_mapping_list->dataMappings, $endpoint, $filename_identifier, $filename);

            $rules = [];
            foreach ($validate as $key => $valid) {
                $field = $validate[$key]['field'];
                $required = $validate[$key]['required'];
                $data_type = $validate[$key]['data_type'];
    
                array_push($rules, [
                    $field =>  $required.$data_type 
                ]);
            }
    
            $rules = call_user_func_array("array_merge", $rules);

            $validation_message = [];
            if ($mapped) {
                foreach ($mapped as $key => $data) {
                    $validation = validator($data, $rules);
                    if(count($validation->messages()) > 0) {
                        $validation_message[] = $validation->messages();
                    }
                }
            } else {
                resolve('filesystem')->forgetDisk(Disk::LOCAL_POS_TO_CDIS);
                app()['config']->set('filesystems.disks.'.Disk::LOCAL_POS_TO_CDIS.'.root', public_path());
                
                Storage::disk(Disk::LOCAL_POS_TO_CDIS)
                ->move($endpoint['source_path'].'/'.$directory.'/'.$filename, 
                $endpoint['failed'].'/'.$directory.'/'.$filename);
    
                $result = new stdClass;
                $result->filename_identifier = $filename_identifier;
                $result->data = [];
                $result->success = false;
    
                return $result;
            }

            if ($validation_message) {
                $validation_message = json_decode(json_encode($validation_message));
    
                resolve('filesystem')->forgetDisk(Disk::LOCAL_POS_TO_CDIS);
                app()['config']->set('filesystems.disks.'.Disk::LOCAL_POS_TO_CDIS.'.root', public_path());
                
                Storage::disk(Disk::LOCAL_POS_TO_CDIS)
                ->move($endpoint['source_path'].'/'.$directory.'/'.$filename, 
                $endpoint['failed'].'/'.$directory.'/'.$filename);
    
                $validated = $this->validationError($validation_message, $filename, $filename_identifier, $endpoint);
            } else {
                $validated = true;
            }

            if ($validated) {
                $result = new stdClass;
                $result->filename_identifier = $filename_identifier;
                $result->data = $mapped;
                $result->success = true;
    
                return $result;
            } else {
                $result = new stdClass;
                $result->filename_identifier = $filename_identifier;
                $result->data = false;
                $result->success = false;
                return $result;
            }
            
        } else {
            $this->warn('Failed conversion with filename '.$filename.' due to no field map detected.');

            $errorExist = ErrorLog::where('filename', $filename)->first();
            if (! $errorExist) {
                $errorLog = ErrorLog::create([
                    'pos_entry' => $endpoint['name'],
                    'filename' => $filename,
                    'status' => 'Failed conversion'
                ]);
    
                ErrorLogDetail::create([
                    'error_log_bid' => $errorLog->bid,
                    'sheet' => "NA",
                    'error_type' => 'No Field Mapping',
                    'description' => 'Failed conversion due to no field map detected.'
                ]);
            }
        }
    }

    /**
     * Mapped the column of POS to CDIS fields
     *
     * @param array $pos_data
     * @param array $data_map
     * @param object $dataMappings
     * @param string $filename_identifier
     * @param string $filename
     * 
     * @return array $mapped
     */
    public function dataMapped($pos_data, $data_map, $dataMappings, $endpoint, $filename_identifier, $filename)
    {
        $failed = false;
        $errors = [];
        $mapped = [];
        foreach ($pos_data as $key => $data) {
            $array = [];
            foreach($data as $columnName => $b) {
                if (in_array($columnName, $data_map)) {
                    $array_key = array_search($columnName, $data_map);
                    if ($b !== null) {
                        $array[$array_key] = $b;
                    } else {
                        $default = $dataMappings->where('field', array_search('account_number', $data_map))->first();
                        $array[$array_key] = $default['default_value'];
                    }
                } else {
                    array_push($errors, [
                        'sheet' => $this->fileNameIdentifier($filename_identifier),
                        'error_type' => 'Wrong column name',
                        'description' => "[column ".$columnName.", row ".($key+1)."] "."Column name missing or mismatch"
                    ]);

                    $failed = true;
                }
            }
            foreach ($data_map as $column => $value) {
                if(! array_key_exists($column, $array)) {
                    $default = $dataMappings->where('field', $column)->first();
                    $array[$column] = $default['default_value'];
                }
            }
            $mapped[] = $array;
        }

        if($failed) {

            $error_log = ErrorLog::create([
                'pos_entry' => $endpoint['name'],
                'filename' => $filename,
                'status' => 'Failed conversion'
            ]);

            foreach($errors as $error) {
                ErrorLogDetail::create([
                    'error_log_bid' => $error_log->bid,
                    'sheet' => $error['sheet'],
                    'error_type' => $error['error_type'],
                    'description' => $error['description']
                ]);
            }

            return false;
        } else {
            return $mapped;
        }
    }

    /**
     * Create a resources if there is an error in data type
     *
     * @param array $validation_message
     * @param string $filename
     * @param string $filename_identifier
     * @param string $name
     * 
     */
    public function validationError($validation_message, $filename, $name, $endpoint)
    {
        if($validation_message) {
            $error_log = ErrorLog::create([
                'pos_entry' => $endpoint['name'],
                'filename' => $filename,
                'status' => 'Failed conversion'
            ]);
        }

        foreach ($validation_message as $key => $error) {
            $data = ((array) $validation_message[$key]);
            if ($data) {
                foreach ($data as $columnName => $error) {
                    ErrorLogDetail::create([
                        'error_log_bid' => $error_log->bid,
                        'sheet' => $this->fileNameIdentifier($name),
                        'error_type' => 'Invalid value',
                        'description' => "[column ".$columnName.", row ".($key+1)."] ".$error[0]
                    ]);
                }
                return false;
            }
            else {
                return true;
            }
        }
    }

    public function defaultValue($dataMappings, $column)
    {
        $data = $dataMappings['dataMappings']->where('field', $column)->first();
        return $data->default_value;
    }

    public function fileNameIdentifier($name)
    {
        return $name == Acronym::TRANSACTION_HEAD ? FileNameIdentifier::TH
        :($name == Acronym::TRANSACTION_DETAIL ? FileNameIdentifier::TD
        :($name == Acronym::PRODUCT ? FileNameIdentifier::PR
        :($name == Acronym::PRODUCT_DISCOUNT ? FileNameIdentifier::PD
        :($name == Acronym::ADDON ? FileNameIdentifier::AD
        :($name == Acronym::PAYMENT_METHOD ? FileNameIdentifier::PM
        :($name == Acronym::CASH_BREAKDOWN ? FileNameIdentifier::ZCB
        :($name == Acronym::CASHIER_SUMMARY ? FileNameIdentifier::ZCS
        :($name == Acronym::ZREAD_HEAD ? FileNameIdentifier::ZH
        :($name == Acronym::REGULAR_DISCOUNT ? FileNameIdentifier::ZRD
        :($name == Acronym::AUDIT_TRAIL ? FileNameIdentifier::AT
        :($name == Acronym::TENDER_DETAILS ? FileNameIdentifier::ZTD
        :($name == Acronym::CASH_BREACKDOWN_HEAD ? FileNameIdentifier::CH 
        :($name == Acronym::CASH_BREACKDOWN_DETAIL ? FileNameIdentifier::CD
        : FileNameIdentifier::DR)))))))))))));
    }
}
