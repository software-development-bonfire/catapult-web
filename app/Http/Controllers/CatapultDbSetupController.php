<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatapultDbSetupRequest;
use App\Repositories\Contracts\CatapultDbSetupRepository;
use Illuminate\Http\Request;
use App\Services\CatapultDBSetupService;
use App\Transformers\CatapultDbSetupTransformer;
use Illuminate\Support\Facades\Lang;

class CatapultDbSetupController extends Controller
{

    private $catapultDbSetupService;

    /**
     * import CatapultDbSetupService.
     *
     * @param  CatapultDbSetupService  $catapultDbSetupService
     *
     */
    public function __construct(CatapultDbSetupService $catapultDbSetupService)
    {
        $this->catapultDbSetupService = $catapultDbSetupService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = app()->make(CatapultDbSetupRepository::class)->list($request->all());

        $list = fractal($list, CatapultDbSetupTransformer::class);

        return $this->successfulResponse($list);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CatapultDbSetupRequest $request)
    {
        return $this->catapultDbSetupService->store($request->validated());
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
    public function update(CatapultDbSetupRequest $request, $id)
    {
        return $this->catapultDbSetupService->update($request->validated(), $id);
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
            $this->catapultDbSetupService->destroy($bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('catapult_db_setup_failed_deleted')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.catapult_db_setup_deleted')
        );
    }
}
