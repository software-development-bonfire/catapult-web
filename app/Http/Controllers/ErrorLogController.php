<?php

namespace App\Http\Controllers;

use App\Enums\ApiEndpoint;
use App\Enums\Directory;
use App\Repositories\Contracts\ErrorLogRepository;
use App\Transformers\ErrorLogTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ErrorLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // dd($request->all());
        $list = app()->make(ErrorLogRepository::class)->list($request->all());

        $list = fractal($list, ErrorLogTransformer::class);

        return $this->successfulResponse($list);
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
    public function store(Request $request)
    {
        //
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

    public function uploadCsv(Request $request)
    {
        $file = $request->file;

        if ($request->endpoint = ApiEndpoint::TRANSACTION) {
            $path = Directory::FOR_CONVERSION_TRANSACTION_TO_CONVERT;
        } else if ($request->endpoint = ApiEndpoint::ZREAD) {
            $path = Directory::FOR_CONVERSION_ZREAD_TO_CONVERT;
        } else if ($request->endpoint = ApiEndpoint::AUDIT_TRAIL) {
            $path = Directory::FOR_CONVERSION_AUDIT_TRAIL_TO_CONVERT;
        } else if ($request->endpoint = ApiEndpoint::CASH_BREAKDOWN) {
            $path = Directory::FOR_CONVERSION_CASH_BREAKDOWN_TO_CONVERT;
        } else {
            $path = Directory::FOR_CONVERSION_CASH_DRAWER_TO_CONVERT;
        }

        resolve('filesystem')->forgetDisk('public');
        app()['config']->set('filesystems.disks.public.root', public_path($path.'/'.$request->path));
        Storage::disk('public')->put('/'.$file->getClientOriginalName(), file_get_contents($file));
    }
}
