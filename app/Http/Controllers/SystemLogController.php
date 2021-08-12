<?php

namespace App\Http\Controllers;

use App\Entities\SystemLog;
use App\Enums\Disk;
use App\Exports\SystemLogExport;
use App\Repositories\Contracts\SystemLogRepository;
use App\Transformers\SystemLogTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SystemLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = app()->make(SystemLogRepository::class)->list($request->all());

        $list = fractal($list, SystemLogTransformer::class);

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

    public function downloadLogs(Request $request)
    {
        $list = app()->make(SystemLogRepository::class)->list($request->all());

        $list = $list->map(function ($list) {
            return [
                0 => $list->bid,
                1 => $list->initiator,
                2 => $list->module_process,
                3 => $list->action,
                4 => $list->description,
            ];
        });

        $model = new SystemLog();
        $headers = $model->getFillable();

        $filename = "System Logs ".Carbon::now()->format('Y-m-d His').".csv";

        Excel::store(new SystemLogExport($headers, $list), $filename, Disk::SYSTEM_LOGS);

        return $filename;
    }
}
