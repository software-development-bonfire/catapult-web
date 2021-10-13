<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApiSetupRequest;
use App\Repositories\Contracts\ApiSetupRepository;
use App\Services\ApiSetupService;
use App\Transformers\ApiSetupTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class ApiSetupController extends Controller
{
    private $apiSetupService;

    /**
     * import ApiSetupService.
     *
     * @param  ApiSetupService  $apiSetupService
     *
     */
    public function __construct(ApiSetupService $apiSetupService)
    {
        $this->apiSetupService = $apiSetupService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = app()->make(ApiSetupRepository::class)->list($request->all());

        $list = fractal($list, ApiSetupTransformer::class);

        return $this->successfulResponse($list);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse
     */
    public function store(ApiSetupRequest $request)
    {
        return $this->apiSetupService->store($request->validated());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(ApiSetupRequest $request, $bid)
    {
        try {
            $this->apiSetupService->update($request->validated(), $bid);
        } catch (\Exception $exception) {
            return $this->errorResponse(null, __('error.api_setup_failed_update'));
        }

        return $this->successfulResponse(null, __('success.api_setup_updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $bid
     * @return \Illuminate\Http\Response
     */
    public function destroy($bid)
    {
        try {
            $this->apiSetupService->destroy($bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('api_setup_failed_deleted')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.api_setup_deleted')
        );
    }
}
