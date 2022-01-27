<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileStorageSetupRequest;
use App\Repositories\Contracts\FileStorageSetupRepository;
use App\Services\FileStorageSetupService;
use App\Transformers\FileStorageSetupTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class FileStorageSetupController extends Controller
{
    private $fileStorageSetupService;

    /**
     * import fileStorageSetupService.
     *
     * @param  FileStorageSetupService  $fileStorageSetupService
     *
     */
    public function __construct(FileStorageSetupService $fileStorageSetupService)
    {
        $this->fileStorageSetupService = $fileStorageSetupService;
    }
    /**
     * Display a listing of the resource.
     *
     * @param  Request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $list = app()->make(FileStorageSetupRepository::class)->list($request->all());

        $list = fractal($list, FileStorageSetupTransformer::class);

        return $this->successfulResponse($list);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  FileStorageSetupRequest  $request
     * @return JsonResponse
     */
    public function store(FileStorageSetupRequest $request)
    {
        try {
            $this->fileStorageSetupService->store($request->validated());
        } catch(\Exception $ex) {
            return $this->errorResponse(
                [],
                __('error.file_storage_setup_failed_create')
            );
        }

        return $this->successfulResponse([], __('success.file_storage_setup_created'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  FileStorageSetupRequest  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(FileStorageSetupRequest $request, $id)
    {
        try {
            $this->fileStorageSetupService->update($request->validated(), $id);
        } catch(\Exception $ex) {
            return $this->errorResponse(
                [],
                __('error.file_storage_setup_failed_update')
            );
        }

         return $this->successfulResponse([], __('success.file_storage_setup_updated'));
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
            $this->fileStorageSetupService->destroy($bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('file_storage_setup_failed_deleted')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.file_storage_setup_deleted')
        );
    }
}
