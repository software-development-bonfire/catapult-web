<?php

namespace App\Services;

use App\Entities\FieldMapping;
use App\Entities\FieldMappingDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FieldMappingSetupService
{
    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     * @return \Illuminate\Http\Response
     */
    public function store($data)
    {
        $data['created_by'] = Auth::user()->bid;
        $details = $data['details'];
        unset($data['details']);
        DB::transaction(function () use ($data, $details){
            $field_mapping = FieldMapping::Create($data);
            foreach ($details as $detail) {
                FieldMappingDetail::create([
                    'field_mapping_bid' => $field_mapping->bid,
                    'required' => $detail['required'],
                    'field' => $detail['field'],
                    'description' => $detail['description'],
                    'mapping_type' => $detail['mapping_type'],
                    'file_name' => $detail['csv_file_name_identifier'],
                    'default_value' => $detail['default_value'],
                ]);
            }
        });
    }

    /**
     * Create the specified resource in storage.
     *
     * @param Array  $data
     * @return \Illuminate\Http\Response
     */
    public function detail_create($data)
    {
        FieldMappingDetail::create($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     * @param int  $bid
     * @return \Illuminate\Http\Response
     */
    public function update($data, $bid)
    {
        $data['updated_by'] = Auth::user()->bid;
        FieldMapping::find($bid)->update($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     * @param int  $bid
     * @return \Illuminate\Http\Response
     */
    public function detail_update($data, $bid)
    {
        FieldMappingDetail::find($bid)->update($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $bid
     * @return \Illuminate\Http\Response
     */
    public function destroy($bid)
    {
        DB::transaction(function () use ($bid){
            FieldMapping::find($bid)->delete();
            FieldMappingDetail::where('field_mapping_bid', $bid)->delete();
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $bid
     * @return \Illuminate\Http\Response
     */
    public function detail_destroy($bid)
    {
        FieldMappingDetail::find($bid)->delete();
    }
}