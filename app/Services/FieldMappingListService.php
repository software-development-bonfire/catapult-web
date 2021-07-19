<?php

namespace App\Services;

use App\Entities\DataMapping;
use App\Entities\FieldMapping;
use App\Entities\FieldMappingList;
use App\Enums\Acronym;
use App\Enums\Disk;
use App\Enums\FileNameIdentifier;
use App\Enums\Status;
use App\Exports\GenerateDefaultCsv;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class FieldMappingListService
{
    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     */
    public function store($data)
    {
        $data['created_by'] = Auth::user()->bid;

        return FieldMappingList::create($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     */
    public function storeDataMapping($data)
    {
        DB::beginTransaction();
        try {
            $field_mapping = FieldMappingList::find($data['field_mapping_list_bid']);

            if ($field_mapping['status'] == Status::ACTIVE) {
                $test = FieldMappingList::where([
                    'status' => Status::ACTIVE,
                    'api_endpoint' => $data['end_point'],
                    'type' => $field_mapping['type']
                    ])->where('bid', '!=', $data['field_mapping_list_bid'])
                ->update(['status' => Status::INACTIVE]);
            }

            $field_mapping->update([
                'status' => $field_mapping['status'],
                'field_mapping_bid' => $data['field_mapping_bid'],
                'api_endpoint' => $data['end_point'],
                'api_version_name' => $data['api_version_name'],
                'updated_by' => Auth::user()->bid
            ]);
            
            foreach ($data['fields'] as $arr) {
                DataMapping::create([
                    'field_mapping_list_bid' => $data['field_mapping_list_bid'],
                    'required' => $arr['required'],
                    'field' => $arr['field'],
                    'description' => $arr['description'],
                    'mapping_type' => $arr['mapping_type'],
                    'file_name' => $arr['file_name'],
                    'default_value' => $arr['default_value'],
                    'column_name' => ($arr['column_name'] == null || $arr['column_name'] == '""') 
                        ? $arr['default_value']
                        : $arr['column_name'],
                ]);
            }

            DB::commit();
            return true;
        } catch (\Throwable $th) {
            DB::rollBack();
            return ['message' => $th->getMessage()];
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     * @param  string  $bid
     */
    public function update($data, $bid)
    {
        DB::beginTransaction();
        try {
            $data['updated_by'] = Auth::user()->bid;
            
            if ($data['status'] == Status::ACTIVE && array_key_exists('api_endpoint', $data)) {

                FieldMappingList::where([
                    'status' => Status::ACTIVE,
                    'api_endpoint' => $data['api_endpoint'],
                    'type' => $data['type']])
                    ->update(['status' => Status::INACTIVE
                ]);

                FieldMappingList::find($bid)->update([
                    'remote_setup_bid' => $data['remote_setup_bid'],
                    'catapult_db_setup_bid' => $data['catapult_db_setup_bid'],
                    'api_setup_bid' => $data['api_setup_bid'],
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'status' => $data['status'],
                    'api_endpoint' => $data['api_endpoint'],
                    'update_by' => $data['updated_by'],
                ]);
            } 
            else {
                FieldMappingList::find($bid)->update($data);
            }

            DB::commit();
            return true;
        } catch (\Throwable $th) {
            DB::rollBack();
            return $th->getMessage();
        }
    }

        /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     * @param  string  $field_mapping_list_bid
     */
    public function updateDataMapping($data, $field_mapping_list_bid)
    {
        DataMapping::where('field_mapping_list_bid', $field_mapping_list_bid)->delete();

        $this->storeDataMapping($data);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $bid
     */
    public function destroy($bid)
    {
        FieldMappingList::find($bid)->delete();
        DataMapping::where('field_mapping_list_bid', $bid)->delete();
    }

    /**
     * generate a listing of csv resource.
     *
     * @param  array  $request
     */
    public function generateCsv($request)
    {
        $transaction = FieldMapping::where(['api_endpoint' => $request['transaction'], 'status' => Status::ACTIVE])
            ->first();

        $fieldMapping = FieldMappingList::with('dataMappings')
            ->where(['field_mapping_bid' => $transaction['bid'], 'status' => Status::ACTIVE])
            ->first();

            $file_path = [];

        switch($request['transaction']) {
            case 'Transactions':
                if ($request['transaction_head'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::TH.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::TRANSACTION_HEAD);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['transaction_detail'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::TD.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::TRANSACTION_DETAIL);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['products'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::PR.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::PRODUCT);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['payment'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::PM.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::PAYMENT_METHOD);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['discounts'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::PD.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::PRODUCT_DISCOUNT);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['add_ons'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::AD.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::ADDON);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
            break;
            case 'Z Read':
                if ($request['zread_head'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::ZH.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::ZREAD_HEAD);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['cash_breakdown'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::ZCB.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::CASH_BREAKDOWN);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['cashier_summary'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::ZCS.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::CASHIER_SUMMARY);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['regular_discount'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::ZRD.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::REGULAR_DISCOUNT);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['tender_details'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::ZTD.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::TENDER_DETAILS);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
            break;
            case 'Cash Breakdown':
                if ($request['cash_breakdown_head'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::CH.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::CASH_BREACKDOWN_HEAD);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['cash_breakdown_detail'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::CD.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::CASH_BREACKDOWN_DETAIL);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
            break;
            case 'Cash Drawer':
                if ($request['cash_drawer'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::DR.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::CASH_DRAWER);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
            break;
            case 'Audit Trail':
                if ($request['audit_trail'] == "true") {
                    $filename = $transaction['api_endpoint'].'-'.FileNameIdentifier::AT.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::AUDIT_TRAIL);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
        }

        return $file_path;
    }
}


