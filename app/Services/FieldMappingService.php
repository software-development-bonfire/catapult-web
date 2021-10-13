<?php

namespace App\Services;

use App\Entities\FieldMapping;
use App\Entities\FieldMappingDetail;
use App\Enums\Acronym;
use App\Enums\Disk;
use App\Enums\FileNameIdentifier;
use App\Enums\Status;
use App\Exports\GenerateDefaultCsv;
use App\Traits\DatabaseTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class FieldMappingService
{
    use DatabaseTransaction;

    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     */
    public function store($data)
    {
        $data['created_by'] = Auth::user()->bid;

        return FieldMapping::create($data);
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
            $fieldMapping = FieldMapping::find($data['field_mapping_bid']);

            if ($fieldMapping['status'] == Status::ACTIVE) {
                FieldMapping::where([
                    'status' => Status::ACTIVE,
                    'data_entry' => $data['data_entry'],
                    'type' => $fieldMapping['type']
                    ])->where('bid', '!=', $data['field_mapping_bid'])
                ->update(['status' => Status::INACTIVE]);
            }

            $fieldMapping->update([
                'status' => $fieldMapping['status'],
                'data_entry' => $data['data_entry'],
                'updated_by' => Auth::user()->bid
            ]);
            
            foreach ($data['fields'] as $arr) {
                $fieldMapping->detail()->create([
                    'required' => $arr['required'],
                    'field' => $arr['field'],
                    'description' => $arr['description'],
                    'mapping_type' => $arr['mapping_type'],
                    'file_name' => $arr['file_name'],
                    'default_value' => $arr['default_value'],
                    'column_name' => ($arr['column_name'] == null || $arr['column_name'] == '""') 
                        ? $arr['default_value']
                        : $arr['column_name'],
                    'reference_column_name' => $arr['reference_column_name'],
                    'head_reference' => $arr['head_reference'],
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
            
            if ($data['status'] == Status::ACTIVE && array_key_exists('data_entry', $data)) {

                FieldMapping::where([
                    'status' => Status::ACTIVE,
                    'data_entry' => $data['data_entry'],
                    'type' => $data['type']])
                    ->update(['status' => Status::INACTIVE
                ]);

                FieldMapping::find($bid)->update([
                    'file_storage_setup_bid' => $data['file_storage_setup_bid'],
                    'catapult_db_setup_bid' => $data['catapult_db_setup_bid'],
                    'api_setup_bid' => $data['api_setup_bid'],
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'status' => $data['status'],
                    'data_entry' => $data['data_entry'],
                    'update_by' => $data['updated_by'],
                ]);
            } 
            else {
                FieldMapping::find($bid)->update($data);
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
     * @param array  $data
     * @param string  $bid
     */
    public function updateDataMapping($data, $bid)
    {
        return $this->transaction(function() use($data, $bid) {
            FieldMappingDetail::where('field_mapping_bid', $bid)->delete();

            $this->storeDataMapping($data);

            return true;
        });
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $bid
     */
    public function destroy($bid)
    {
        FieldMapping::find($bid)->delete();
        FieldMappingDetail::where('field_mapping_list_bid', $bid)->delete();
    }

    /**
     * generate a listing of csv resource.
     *
     * @param  array  $request
     */
    public function generateCsv($request)
    {
        $transaction = FieldMapping::where(['data_entry' => $request['transaction'], 'status' => Status::ACTIVE])
            ->first();

        $fieldMapping = FieldMapping::with('dataMappings')
            ->where(['field_mapping_bid' => $transaction['bid'], 'status' => Status::ACTIVE])
            ->first();

            $file_path = [];

        switch($request['transaction']) {
            case 'Transactions':
                if ($request['transaction_head'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::TH.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::TRANSACTION_HEAD);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['transaction_detail'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::TD.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::TRANSACTION_DETAIL);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['products'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::PR.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::PRODUCT);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['payment'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::PM.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::PAYMENT_METHOD);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['discounts'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::PD.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::PRODUCT_DISCOUNT);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['add_ons'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::AD.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::ADDON);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
            break;
            case 'Z Read':
                if ($request['zread_head'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::ZH.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::ZREAD_HEAD);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['cash_breakdown'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::ZCB.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::CASH_BREAKDOWN);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['cashier_summary'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::ZCS.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::CASHIER_SUMMARY);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['regular_discount'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::ZRD.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::REGULAR_DISCOUNT);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['tender_details'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::ZTD.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::TENDER_DETAILS);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
            break;
            case 'Cash Breakdown':
                if ($request['cash_breakdown_head'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::CH.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::CASH_BREACKDOWN_HEAD);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
                if ($request['cash_breakdown_detail'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::CD.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::CASH_BREACKDOWN_DETAIL);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
            break;
            case 'Cash Drawer':
                if ($request['cash_drawer'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::DR.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::CASH_DRAWER);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
            break;
            case 'Audit Trail':
                if ($request['audit_trail'] == "true") {
                    $filename = $transaction['data_entry'].'-'.FileNameIdentifier::AT.'.csv';
                    $th = $fieldMapping['dataMappings']->where('file_name', Acronym::AUDIT_TRAIL);
                    Excel::store(new GenerateDefaultCsv($th), $filename, Disk::DEFAULT_CSV);
                    $file_path[] = 'Default csv/'.($filename);
                }
        }

        return $file_path;
    }
}


