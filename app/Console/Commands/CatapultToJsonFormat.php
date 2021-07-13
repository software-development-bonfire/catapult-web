<?php

namespace App\Console\Commands;

use App\Entities\FieldMappingList;
use App\Enums\ApiEndpoint;
use App\Enums\Directory;
use App\Enums\Disk;
use App\Enums\MappingType;
use App\Enums\Status;
use App\Exports\PosToCdisExport;
use App\Http\Requests\PosToCdisValidation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
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
        $endpoints = [
                [
                    'name' => ApiEndpoint::TRANSACTION,
                    'source_path' => Directory::FOR_CONVERSION_TRANSACTION_TO_CONVERT,
                    'processed' => Directory::FOR_CONVERSION_TRANSACTION_PROCESSED,
                    'failed' => Directory::FOR_CONVERSION_TRANSACTION_FAILED,
                    'converted' => Directory::CONVERTED_TRANSACTION_TO_SYNC
                ]
            ];

        foreach($endpoints as $endpoint) {
            resolve('filesystem')->forgetDisk(Disk::LOCAL_POS_TO_CDIS);
            app()['config']->set('filesystems.disks.'.Disk::LOCAL_POS_TO_CDIS.'.root', public_path($endpoint['source_path']));
            $files = Storage::disk(Disk::LOCAL_POS_TO_CDIS)->files();
            
            foreach($files as $file) {
                $filename = substr($file, strrpos($file, '/'));
                $extension = substr($file, strrpos($file, '.') + 1);
                
                switch($extension) {
                    case 'csv':
                        $this->ConvertToJson($filename, $endpoint);
                        break;
                    default:
                        dd($extension);
                }
                
            }
        }
    }

    /**
     * Convert File into Json.
     *
     * @param string $filename
     * @return mixed
     */
    public function ConvertToJson($filename, $endpoint)
    {
        $contents = Excel::toArray(new PosToCdisExport, public_path($endpoint['source_path'].'/'.$filename));

        $pos_data = array();

        $data = $contents[0];

        $indexes = [];
        foreach($data as $key => $c) {
            if($key === 0) {
                $indexes = $c;
            } else {
                // dd($indexes);
                $array_data = [];
                // $object = new stdClass;
                foreach($c as $a => $b) {
                    // $index = $indexes[$a];
                    // $object->$index = $b; 
                    $array_data[$indexes[$a]] = $b;
                }
                $pos_data[] = $array_data;
                // array_push($pos_data, $object);
            }
        }
        
        $this->validation($pos_data, $filename, $endpoint);
    }

    /**
     * Convert File into Json.
     *
     * @param array $pos_data
     * @return mixed
     */
    public function validation($pos_data, $filename, $endpoint)
    {
        $filename_identifier = substr($filename, 0, 2);

        $field_mapping_list = FieldMappingList::with('dataMappings')
            ->where([
                'type' => MappingType::POS_TO_CDIS,
                'status' => Status::ACTIVE,
                'api_endpoint' => $endpoint['name']
            ])->first();

        $validate = [];

        $data_map = [];

        foreach ($field_mapping_list->dataMappings as $key => $data) {
            array_push($data_map, [
                $data->field => $data->column_name === '""' ? $data->field : $data->column_name,
            ]);

            array_push($validate, [
                "field" => $data->column_name === '""' ? $data->field : $data->column_name,
                "required" => $data->required === 1 ? "required" : "sometimes",
                "data_type" => $data->mapping_type == "VARCHAR" ? "string"
                    :( $data->mapping_type == "DATE" ? "date_format:d/m/Y" 
                    :( $data->mapping_type == "DATETIME" ? 'date_format:"d/m/Y H:i:s"' 
                    :( $data->mapping_type == "TIME" ? "string" 
                    :( $data->mapping_type == "TEXT" ? "string|max:1024" 
                    :( $data->mapping_type == "INT" ? "numeric" 
                    :( $data->mapping_type == "DECIMAL" ? "regex:^(?:[1-9]\d+|\d)(?:\,\d\d)?$"
                    :  "numeric|max:20" ))))))
            ]);
        }

        $data_map = call_user_func_array("array_merge",$data_map);

        foreach ($validate as $key => $value) {
            $array_key = array_search($validate[$key]['field'], $data_map);
            $validate[$key]['field'] = $array_key;
        }

        $mapped = [];
        foreach ($pos_data as $key => $data) {
            $array = [];
            foreach($data as $columnName => $b) {
                if (in_array($columnName, $data_map)) {
                    $array_key = array_search($columnName, $data_map);
                    $array[$array_key] = $b;
                } else {
                    $array["error"] = $b;
                    //error logs if csv column name encoded in data mapping not existed or wrong column name
                    //create error logs resources
                    // dd([
                    //     'sheet' => "Transaction Head",
                    //     'error_type' => "Wrong column name or missing Column ".columnName,
                    //     'description' => "Column name mismatch".columnName
                    // ]);
                }
            }
            $mapped[] = $array;
        }

        $rules = [];
        foreach ($validate as $key => $valid) {
            $field = $validate[$key]['field'];
            $required = $validate[$key]['required'];
            $data_type = $validate[$key]['data_type'];

            array_push($rules, [
                $field =>  $required.'|'.$data_type 
            ]);
        }
        $rules = call_user_func_array("array_merge", $rules);
        dd($rules);

        $messages = [];

        foreach ($validate as $key => $valid) {
            if($valid['required'] == 'required') {
                array_push($messages, [
                    $valid['field'].".required" =>  __('validation.required', [ 'attribute' => $valid['field'] ]),
                ]);
            }
            if($valid['data_type'] == 'numeric') {
                array_push($messages, [
                    $valid['field'].".numeric" =>  __('validation.numeric', [ 'attribute' => $valid['field'] ]),
                ]);
            }
            if($valid['data_type'] == 'date_format') {
                array_push($messages, [
                    $valid['field'].".date_format" =>  __('validation.date_format', [ 'attribute' => $valid['field'] ]),
                ]);
            }
            if($valid['data_type'] == 'regex:^(?:[1-9]\d+|\d)(?:\,\d\d)?$') {
                array_push($messages, [
                    $valid['field'].".regex:^(?:[1-9]\d+|\d)(?:\,\d\d)?$" =>  __('validation.date_format', [ 'attribute' => $valid['field'] , 'format' => 'of decimal']),
                ]);
            }

        }

        // dd($messages);
        $validation = Validator($mapped, $rules, $messages);
        // dd($validation->messages());
    }
}
