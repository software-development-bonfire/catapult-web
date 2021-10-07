<?php

namespace App\Http\Controllers;

use App\Http\Requests\FieldMappingDetailRequest;
use App\Http\Requests\FieldMappingPresetRequest;
use App\Http\Requests\MappingDetailsCreateRequest;
use App\Repositories\Contracts\FieldMappingPresetRepository;
use App\Services\FieldMappingPresetService;
use App\Transformers\FieldMappingPresetTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class FieldMappingPresetController extends Controller
{
    public $fieldMappingPresetService;

    /**
     * @param  FieldMappingPresetService  $fieldMappingPresetService
     *
     */
    public function __construct(FieldMappingPresetService $fieldMappingPresetService)
    {
        $this->fieldMappingPresetService = $fieldMappingPresetService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function view()
    {
        return view('field-mapping-preset.list');
    }

    /**
     * Displays the product detail page.
     *
     * @return \Illuminate\Http\Response
     */
    public function detail(Request $request)
    {
        $filters = (object) stringToJson($request->all());

        $detail = app()->make(FieldMappingPresetRepository::class)->getDetail($filters);

        return view('field-mapping-preset.detail', compact('detail'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $list = app()->make(FieldMappingPresetRepository::class)->list($request->all());

        $list = fractal($list, FieldMappingPresetTransformer::class);

        return $this->successfulResponse($list);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(FieldMappingPresetRequest $request)
    {
        try {
            $data = $this->fieldMappingPresetService->store($request->validated());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.field_mapping_preset_failed_create')
            );
        }

        return $this->successfulResponse(
            $data,
            Lang::get('success.field_mapping_preset_created')
        );
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  MappingDetailsCreateRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function storeDetails(MappingDetailsCreateRequest $request)
    {
        try {
            $data = $this->fieldMappingSetupService->storeDetails($request->validated());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.field_mapping_detail_failed_create')
            );
        }
        return $this->successfulResponse(
            $data,
            Lang::get('success.field_mapping_detail_created')
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  MappingDetailsCreateRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function storePreset(Request $request)
    {
        try {
            $data = $this->fieldMappingSetupService->storePreset($request->all());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                $th->getMessage()
            );
        }
        return $this->successfulResponse(
            $data,
            Lang::get('success.field_mapping_detail_created')
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  FieldMappingPresetRequest  $request
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function update(FieldMappingPresetRequest $request, $bid)
    {
        try {
            $this->fieldMappingPresetService->update($request->validated(), $bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.field_mapping_preset_failed_update')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.field_mapping_preset_updated')
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  FieldMappingDetailRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function storeDetail(FieldMappingDetailRequest $request)
    {
        try {
            $data = $this->fieldMappingPresetService->storeDetail($request->validated());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.field_mapping_detail_failed_create')
            );
        }
        return $this->successfulResponse(
            $data,
            Lang::get('success.field_mapping_detail_created')
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  FieldMappingDetailRequest  $request
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function updateDetail(FieldMappingDetailRequest $request, $bid)
    {
        try {
            $data = $this->fieldMappingPresetService->updateDetail($request->validated(), $bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.field_mapping_detail_failed_update')
            );
        }
        return $this->successfulResponse(
            $data,
            Lang::get('success.field_mapping_detail_updated')
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function destroy($bid)
    {
        try {
            $this->fieldMappingPresetService->destroy($bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.field_mapping_preset_failed_delete')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.field_mapping_preset_deleted')
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function detailDestroy($bid)
    {
        try {
            $this->fieldMappingPresetService->detailDestroy($bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.field_mapping_detail_failed_delete')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.field_mapping_detail_deleted')
        );
    }
}
