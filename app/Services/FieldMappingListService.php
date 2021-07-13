<?php

namespace App\Services;

use App\Entities\DataMapping;
use App\Entities\FieldMappingList;
use App\Enums\Status;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

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
}


