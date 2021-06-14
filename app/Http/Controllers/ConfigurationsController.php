<?php

namespace App\Http\Controllers;

use App\Services\ConfigurationService;
use App\Entities\Configuration;
use App\Http\Requests\SyncingSetupRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class ConfigurationsController extends Controller
{
    private $configurationService;

    /**
     * import ConfigurationService.
     *
     * @param  ConfigurationService  $configurationService
     *
     */
    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function view()
    {
        return view('configurations.list');
    }

    public function index()
    {
        return Configuration::all();
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
    public function store(SyncingSetupRequest $request)
    {
        try {
            $this->configurationService->store($request->validated());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.syncing_setup_failed_update')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.syncing_setup_updated')
        );
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
