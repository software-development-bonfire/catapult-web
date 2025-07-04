<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Repositories\Contracts\BranchUniversalOrderSummaryRepository;
use App\Transformers\BranchUniversalOrderSummaryTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;

class BranchUniversalOrderSummaryController extends Controller
{
    /**
     * Display a initial data of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('branch-universal-order-summary.list');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $filters = stringToJson($request->get('filters'));
        $sort = (object) stringToJson($request->get('sort'));

        $list = app()->make(BranchUniversalOrderSummaryRepository::class)->list($filters, $sort);
        $list = fractal($list, BranchUniversalOrderSummaryTransformer::class);
        return $this->successfulResponse(['list' => $list]);
    }
}
