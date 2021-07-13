<?php

namespace App\Http\Controllers;

use App\Http\Requests\DataMappingRequest;
use App\Http\Requests\FieldMappingListRequest;
use App\Repositories\Contracts\RemoteSetupRepository;
use App\Repositories\Contracts\CatapultDbSetupRepository;
use App\Repositories\Contracts\ApiSetupRepository;
use App\Repositories\Contracts\FieldMappingListRepository;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Services\FieldMappingListService;
use App\Transformers\CatapultDbSetupTransformer;
use App\Transformers\RemoteSetupTransformer;
use App\Transformers\ApiSetupTransformer;
use App\Transformers\FieldMappingSetupTransformer;
use App\Transformers\FieldMappingTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class FieldMappingController extends Controller
{
    /**
     * @param  FieldMappingListService  $fieldMappingListService
     *
     */
    public function __construct(FieldMappingListService $fieldMappingListService)
    {
        $this->fieldMappingListService = $fieldMappingListService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = app()->make(FieldMappingListRepository::class)->list($request->all());

        $list = fractal($list, FieldMappingTransformer::class);

        return $this->successfulResponse($list);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getList(Request $request)
    {
        if ($request->type === "remote") {
            $class = RemoteSetupRepository::class;
            $transformer = RemoteSetupTransformer::class;
        } else if ($request->type === "catapult") {
            $class = CatapultDbSetupRepository::class;
            $transformer = CatapultDbSetupTransformer::class;
        } else {
            $class = ApiSetupRepository::class;
            $transformer = ApiSetupTransformer::class;
        }

        $list = app()->make($class)->list($request->all());

        $list = fractal($list, $transformer);

        return $this->successfulResponse($list);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getEndpoint(Request $request)
    {
        $list = app()->make(FieldMappingRepository::class)->getEndpoints($request->all());

        $list = fractal($list, FieldMappingSetupTransformer::class);

        return $this->successfulResponse($list);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function view()
    {
        return view('field-mapping.list');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function detail()
    {
        return view('field-mapping.detail');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  FieldMappingListRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(FieldMappingListRequest $request)
    {
        try {
            $data = $this->fieldMappingListService->store($request->validated());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.field_mapping_connection_failed_create')
            );
        }
        return $this->successfulResponse(
            $data,
            Lang::get('success.field_mapping_connection_created')
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  DataMappingRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function storeDataMapping(DataMappingRequest $request)
    {
        try {
            $data = $this->fieldMappingListService->storeDataMapping($request->validated());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.data_mapping_failed_create')
            );
        }
        return $this->successfulResponse(
            $data,
            Lang::get('success.data_mapping_created')
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  FieldMappingListRequest  $request
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function update(FieldMappingListRequest $request, $bid)
    {
        try {
            $this->fieldMappingListService->update($request->validated(), $bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.field_mapping_connection_failed_update')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.field_mapping_connection_updated')
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  DataMappingRequest  $request
     * @param  string  $field_mapping_list_bid
     * @return \Illuminate\Http\Response
     */
    public function updateDataMapping(DataMappingRequest $request, $field_mapping_list_bid)
    {
        try {
            $this->fieldMappingListService->updateDataMapping($request->validated(), $field_mapping_list_bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.data_mapping_failed_update')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.data_mapping_updated')
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
            $this->fieldMappingListService->destroy($bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.data_mapping_failed_delete')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.data_mapping_deleted')
        );
    }
}
