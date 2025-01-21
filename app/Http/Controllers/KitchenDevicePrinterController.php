<?php

namespace App\Http\Controllers;

use App\Http\Requests\KitchenPrinterRequest;
use App\Http\Requests\UserAccountRequest;
use App\Repositories\Contracts\KitchenPrinterRepository;
use App\Repositories\Contracts\UserAccountRepository;
use App\Services\KitchenDevicePrinterService;
use App\Transformers\KitchenPrinterTransformer;
use App\Transformers\UserAccountTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class KitchenDevicePrinterController extends Controller
{
    public $kitchenDevicePrinterService;

    /**
     * @param  KitchenDevicePrinterService  $kitchenDevicePrinterService
     *
     */
    public function __construct(KitchenDevicePrinterService $kitchenDevicePrinterService)
    {
        $this->middleware('has-permission:view.kitchen_device_printer')->only('view');
        $this->kitchenDevicePrinterService = $kitchenDevicePrinterService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = app()->make(KitchenPrinterRepository::class)->list($request->all());
        
        $list = fractal($list, KitchenPrinterTransformer::class);

        return $this->successfulResponse($list);
    }

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function view()
    {
        return view('kitchen-device-printer.list');
    }
   

    /**
     * Update the specified resource in storage.
     *
     * @param  KitchenPrinterRequest  $request
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function update(KitchenPrinterRequest $request, $bid)
    {
        try {
            $this->kitchenDevicePrinterService->update($request->validated(), $bid);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.user_failed_update')
            );
        }
        return $this->successfulResponse(
            [],
            Lang::get('success.user_updated')
        );
    }
}
