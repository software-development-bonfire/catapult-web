<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApiSetupRequest;
use App\Repositories\Contracts\ApiSetupRepository;
use App\Services\ApiSetupService;
use App\Transformers\ApiSetupTransformer;
use Illuminate\Http\Request;

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
     * @return \Illuminate\Http\Response
     */
    public function store(ApiSetupRequest $request)
    {
        return $this->apiSetupService->store($request->validated());
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ApiSetupRequest $request, $id)
    {
        return $this->apiSetupService->update($request->validated(), $id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return $this->apiSetupService->destroy($id);
    }
}
