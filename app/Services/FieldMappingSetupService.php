<?php

namespace App\Services;

use App\Entities\FieldMapping;
use App\Entities\FieldMappingDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FieldMappingSetupService
{
    /**
     * Create the specified resource in storage.
     *
     * @param Array  $data
     * @return \Illuminate\Http\Response
     */
    public function store($data)
    {
        $data['created_by'] = Auth::user()->bid;
        $field_mapping = FieldMapping::Create($data);
        return $field_mapping;
    }

    /**
     * Create the specified resource in storage.
     *
     * @param Array  $data
     * @return \Illuminate\Http\Response
     */
    public function storeDetails($data)
    {
        DB::transaction(function () use ($data){
            FieldMapping::find($data['bid'])->update($data);
            foreach ($data['details'] as $detail) {
                FieldMappingDetail::create([
                    'field_mapping_bid' => $data['bid'],
                    'required' => $detail['required'],
                    'field' => $detail['field'],
                    'description' => $detail['description'],
                    'mapping_type' => $detail['mapping_type'],
                    'file_name' => $detail['file_name'],
                    'default_value' => $detail['default_value'],
                    'column_name' => $detail['column_name'],
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
    public function detailCreate($data)
    {
        FieldMappingDetail::create($data);
    }

    /**
     * Create the specified resource in storage.
     *
     * @param Array  $data
     * @return \Illuminate\Http\Response
     */
    public function storePreset($data)
    {
        $presets = [];
        DB::beginTransaction();
        try {
            FieldMappingDetail::where('field_mapping_bid', $data['bid'])->delete();
            foreach ($data['details'] as $preset) {
                $array = FieldMappingDetail::create([
                    'field_mapping_bid' => $data['bid'],
                    'required' => $preset['required'],
                    'field' => $preset['field'],
                    'description' => $preset['description'],
                    'mapping_type' => $preset['mapping_type'],
                    'file_name' => $preset['file_name'],
                    'default_value' => $preset['default_value'],
                    'column_name' => $preset['column_name'],
                ]);
                array_push($presets, $array);
            }

            DB::commit();
            return $presets;
        } catch (\Throwable $th) {
            DB::rollBack();
            return $th->getMessage();
        }
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
    public function detailUpdate($data, $bid)
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
    public function detailDestroy($bid)
    {
        FieldMappingDetail::find($bid)->delete();
    }
}