<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\ErrorLogRepository;
use App\Services\DashboardService;
use App\Transformers\DashboardSummaryTransformer;
use App\Transformers\ErrorLogTransformer;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('has-permission:view.dashboard')->only('index');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('dashboard');
    }

    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function summary(Request $request)
    {
        $filters = (object) stringToJson($request->get('filters'));
        
        $summary = app()->make(DashboardService::class)->getSummary($filters);
        $summary = fractal($summary, DashboardSummaryTransformer::class);

        $list = app()->make(ErrorLogRepository::class)->list($filters);

        $list = fractal($list, ErrorLogTransformer::class);

        return $this->successfulResponse([
            'summary' => $summary,
            'logs' => $list,
        ]);
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
}
