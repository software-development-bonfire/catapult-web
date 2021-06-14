<?php

namespace App\Http\Controllers;

use App\Entities\RemoteSetup;
use App\Http\Requests\RemoteSetupRequest;
use App\Repositories\Contracts\RemoteSetupRepository;
use App\Services\RemoteSetupService;
use App\Transformers\RemoteSetupTransformer;
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\ResponseJson
     */
    public function store(RemoteSetupRequest $request)
    {
        return $this->remoteSetupService->store($request->validated());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  RemoteSetupRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(RemoteSetupRequest $request, $id)
    {
        return $this->remoteSetupService->update($request->validated(), $id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
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
