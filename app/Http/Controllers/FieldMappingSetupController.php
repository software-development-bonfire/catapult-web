<?php

namespace App\Http\Controllers;

use App\Entities\FieldMapping;
use App\Http\Requests\FieldMappingDetailRequest;
use App\Http\Requests\FieldMappingSetupRequest;
use App\Http\Requests\MappingDetailsCreateRequest;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Services\FieldMappingSetupService;
use App\Transformers\FieldMappingSetupTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class FieldMappingSetupController extends Controller
{
    /**
     * @param  FieldMappingSetupService  $fieldMappingSetupService
     *
     */
    public function __construct(FieldMappingSetupService $fieldMappingSetupService)
    {
        $this->fieldMappingSetupService = $fieldMappingSetupService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function view()
    {
        return view('field-mapping-setup.list');
    }

    /**
     * Displays the product detail pagge.
     *
     * @return \Illuminate\Http\Response
     */
    public function detail()
    {
        return view('field-mapping-setup.detail');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = app()->make(FieldMappingRepository::class)->list($request->all());

        $list = fractal($list, FieldMappingSetupTransformer::class);

        return $this->successfulResponse($list);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(FieldMappingSetupRequest $request)
    {
        try {
            $data = $this->fieldMappingSetupService->store($request->validated());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.field_mapping_setup_failed_create')
            );
        }
        return $this->successfulResponse(
            $data,
            Lang::get('success.field_mapping_setup_created')
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
     * @param  FieldMappingSetupRequest  $request
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function update(FieldMappingSetupRequest $request, $bid)
    {
        try {
            $this->fieldMappingSetupService->update($request->validated(), $bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.field_mapping_setup_failed_update')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.field_mapping_setup_updated')
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  FieldMappingDetailRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function detailCreate(FieldMappingDetailRequest $request)
    {
        try {
            $this->fieldMappingSetupService->detailCreate($request->validated());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.field_mapping_detail_failed_create')
            );
        }
        return $this->successfulResponse(
            [],
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
    public function detailUpdate(FieldMappingDetailRequest $request, $bid)
    {
        try {
            $data = $this->fieldMappingSetupService->detailUpdate($request->validated(), $bid);
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
            $this->fieldMappingSetupService->destroy($bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.field_mapping_setup_failed_delete')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.field_mapping_setup_deleted')
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
            $this->fieldMappingSetupService->detailDestroy($bid);
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
