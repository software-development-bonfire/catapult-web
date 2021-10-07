<?php

namespace App\Http\Controllers;

use App\Http\Requests\RemoteSetupRequest;
use App\Repositories\Contracts\RemoteSetupRepository;
use App\Services\RemoteSetupService;
use App\Transformers\RemoteSetupTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class RemoteSetupController extends Controller
{
    private $remoteSetupService;

    /**
     * import RemoteSetupService.
     *
     * @param  RemoteSetupService  $remoteSetupService
     *
     */
    public function __construct(RemoteSetupService $remoteSetupService)
    {
        $this->remoteSetupService = $remoteSetupService;
    }
    /**
     * Display a listing of the resource.
     *
     * @param  Request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $list = app()->make(RemoteSetupRepository::class)->list($request->all());

        $list = fractal($list, RemoteSetupTransformer::class);

        return $this->successfulResponse($list);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  RemoteSetupRequest  $request
     * @return JsonResponse
     */
    public function store(RemoteSetupRequest $request)
    {
        try {
            $this->remoteSetupService->store($request->validated());
        } catch(\Exception $ex) {
            return $this->errorResponse(
                [],
                __('error.remote_setup_failed_create')
            );
        }

        return $this->successfulResponse([], __('success.remote_setup_created'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  RemoteSetupRequest  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(RemoteSetupRequest $request, $id)
    {
        try {
            $this->remoteSetupService->update($request->validated(), $id);
        } catch(\Exception $ex) {
            return $this->errorResponse(
                [],
                __('error.remote_setup_failed_update')
            );
        }

         return $this->successfulResponse([], __('success.remote_setup_updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $bid
     * @return JsonResponse
     */
    public function destroy($bid)
    {
        try {
            $this->remoteSetupService->destroy($bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('remote_setup_failed_deleted')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.remote_setup_deleted')
        );
    }
}
