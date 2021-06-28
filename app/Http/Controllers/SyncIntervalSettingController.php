<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncIntervalSettingRequest;
use App\Repositories\Contracts\SyncIntervalSettingRepository;
use App\Services\SyncIntervalSettingService;
use App\Transformers\SyncIntervalSettingTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class SyncIntervalSettingController extends Controller
{
    /**
     * @param  SyncIntervalSettingService  $syncIntervalSettingService
     *
     */
    public function __construct(SyncIntervalSettingService $syncIntervalSettingService)
    {
        $this->middleware('has-permission:view.sync_interval_setting')->only('view');
        $this->syncIntervalSettingService = $syncIntervalSettingService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = app()->make(SyncIntervalSettingRepository::class)->list($request->all());
        
        $list = fractal($list, SyncIntervalSettingTransformer::class);

        return $this->successfulResponse($list);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function view()
    {
        return view('sync-interval-setting.list');
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
    public function store(SyncIntervalSettingRequest $request)
    {
        try {
            $this->syncIntervalSettingService->store($request->validated());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.sync_interval_setting_failed_create')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.sync_interval_setting_created')
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
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function update(SyncIntervalSettingRequest $request, $bid)
    {
        try {
            $this->syncIntervalSettingService->update($request->validated(), $bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.sync_interval_setting_failed_update')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.sync_interval_setting_updated')
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
            $this->syncIntervalSettingService->destroy($bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.sync_interval_setting_failed_update')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.sync_interval_setting_updated')
        );
    }
}
