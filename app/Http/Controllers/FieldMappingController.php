<?php

namespace App\Http\Controllers;

use App\Entities\FieldMappingDetail;
use App\Enums\MappingType;
use App\Http\Requests\DataMappingRequest;
use App\Http\Requests\FieldMappingRequest;
use App\Repositories\Contracts\FileStorageSetupRepository;
use App\Repositories\Contracts\CatapultDbSetupRepository;
use App\Repositories\Contracts\ApiSetupRepository;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Repositories\Contracts\FieldMappingPresetRepository;
use App\Repositories\Contracts\SyncEntryRepository;
use App\Services\FieldMappingService;
use App\Transformers\CatapultDbSetupTransformer;
use App\Transformers\FieldMappingDetailTransformer;
use App\Transformers\FieldMappingPresetDataEntriesTransformer;
use App\Transformers\FileStorageSetupTransformer;
use App\Transformers\ApiSetupTransformer;
use App\Transformers\FieldMappingPresetTransformer;
use App\Transformers\FieldMappingTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Spatie\Fractalistic\ArraySerializer;

class FieldMappingController extends Controller
{
    public $fieldMappingService;
    
    /**
     * @param  FieldMappingService  $fieldMappingService
     */
    public function __construct(FieldMappingService $fieldMappingService)
    {
        $this->fieldMappingService = $fieldMappingService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $list = app()->make(FieldMappingRepository::class)->list($request->all());

        $list = fractal($list, FieldMappingTransformer::class);

        return $this->successfulResponse($list);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function dataMappingList(Request $request)
    {
        return FieldMappingDetail::where('field_mapping_bid', $request->field_mapping_bid)->get();
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getList(Request $request)
    {
        if ($request->type === "file_storage") {
            $class = FileStorageSetupRepository::class;
            $transformer = FileStorageSetupTransformer::class;
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
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getDataEntries(Request $request)
    {
        $filters = (object) stringToJson($request->filters);

        $list = app()->make(FieldMappingPresetRepository::class)->getDataEntries($filters);

        $list = fractal($list, FieldMappingPresetDataEntriesTransformer::class);

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
     * Displays the product detail page.
     *
     * @return \Illuminate\Http\Response
     */
    public function detail(Request $request)
    {
        $filters = (object) stringToJson($request->all());

        $detail = app()->make(FieldMappingRepository::class)->getDetail($filters);

        $detail = fractal($detail, FieldMappingDetailTransformer::class)->serializeWith(new ArraySerializer());

        $detail = json_encode($detail);

        return view('field-mapping.detail', compact('detail'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  FieldMappingRequest  $request
     * @return JsonResponse
     */
    public function store(FieldMappingRequest $request)
    {
        try {
            $data = $this->fieldMappingService->store($request->all());
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
     * @return JsonResponse
     */
    public function storeDataMapping(DataMappingRequest $request)
    {
        try {
            $data = $this->fieldMappingService->storeDataMapping($request->all());
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
     * @param  FieldMappingRequest  $request
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function update(FieldMappingRequest $request, $bid)
    {
        try {
            $this->fieldMappingService->update($request->all(), $bid);
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
    public function updateDataMapping(DataMappingRequest $request, $bid)
    {
        try {
            $this->fieldMappingService->updateDataMapping($request->all(), $bid);
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
            $this->fieldMappingService->destroy($bid);
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

    /**
     * generate a listing of csv resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateCsv(Request $request)
    {
        try {
            $data = $this->fieldMappingService->generateCsv($request->all());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.generate_csv_failed_create')
            );
        }
        return $this->successfulResponse(
            $data,
            Lang::get('success.generate_csv_created')
        );
    }
}
