<?php

namespace App\Services;

use App\Entities\FieldMappingPreset;
use App\Entities\FieldMappingPresetDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FieldMappingPresetService
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
        $field_mapping = FieldMappingPreset::create($data);
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
            FieldMappingPreset::find($data['bid'])->update($data);
            foreach ($data['details'] as $detail) {
                FieldMappingPresetDetail::create([
                    'field_mapping_bid' => $data['bid'],
                    'required' => $detail['required'],
                    'field' => $detail['field'],
                    'description' => $detail['description'],
                    'mapping_type' => $detail['mapping_type'],
                    'file_name' => $detail['file_name'],
                    'default_value' => $detail['default_value'],
                    'column_name' => $detail['column_name'],
                    'reference_column_name' => $detail['reference_column_name'],
                    'head_reference' => $detail['head_reference'],
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
    public function storeDetail($data)
    {
        return FieldMappingPresetDetail::create($data);
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
            FieldMappingPresetDetail::where('field_mapping_bid', $data['bid'])->delete();
            foreach ($data['details'] as $preset) {
                $array = FieldMappingPresetDetail::create([
                    'field_mapping_bid' => $data['bid'],
                    'required' => $preset['required'],
                    'field' => $preset['field'],
                    'description' => $preset['description'],
                    'mapping_type' => $preset['mapping_type'],
                    'file_name' => $preset['file_name'],
                    'default_value' => $preset['default_value'],
                    'column_name' => $preset['column_name'],
                    'reference_column_name' => $preset['reference_column_name'],
                    'head_reference' => $preset['head_reference'],
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
        FieldMappingPreset::find($bid)->update($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Array  $data
     * @param int  $bid
     * @return \Illuminate\Http\Response
     */
    public function updateDetail($data, $bid)
    {
        FieldMappingPresetDetail::find($bid)->update($data);
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
            FieldMappingPreset::find($bid)->delete();
            FieldMappingPresetDetail::where('field_mapping_bid', $bid)->delete();
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $bid
     * @return \Illuminate\Http\Response
     */
    public function destroyDetail($bid)
    {
        FieldMappingPresetDetail::find($bid)->delete();
    }
}
