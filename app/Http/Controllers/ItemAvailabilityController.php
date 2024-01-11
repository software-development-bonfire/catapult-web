<?php

namespace App\Http\Controllers;

use App\Entities\DeviceSettings;
use App\Listeners\ProductListListener;
use App\Repositories\Contracts\DeviceSettingsRepository;
use App\Repositories\Contracts\ItemAvailabilityRepository;
use App\Services\ItemAvailabilityService;
use App\Transformers\ItemAvailabilityTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Enums\DeviceType;
use App\Events\MessageEvent;
use Illuminate\Support\Facades\Lang;

class ItemAvailabilityController extends Controller
{
    /**
     * Display a initial data of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $header = app()->make(DeviceSettingsRepository::class)->list([], true);
        $header = $this->getTerminals($header);
        $header = json_encode($header['header']);
        // $this->store();

        return view('item-availability.list', compact('header'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $filters = stringToJson($request->get('filters'));

        $header = app()->make(DeviceSettingsRepository::class)->list([], true);
        $header = $this->getTerminals($header);
        $terminals = $header['terminals'];

        $list = app()->make(ItemAvailabilityRepository::class)->list($filters, $terminals);
        $list = fractal($list, new ItemAvailabilityTransformer($terminals));
        return $this->successfulResponse(['list' => $list, 'filters' => $filters]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request $request
     * @return JsonResponse
     */
    // public function store(Request $request)
    // public function store()
    // {
    //     $data = [];
    //     $data1 = $this->siriusPosDataOne();
    //     $data2 = $this->siriusPosDataTwo();
    //     $data3 = $this->kioskPosDataOne();

    //     $data[] = $data1;
    //     $data[] = $data2;
    //     $data[] = $data3;

    //     try {
    //         $data = app()->make(ItemAvailabilityService::class)->store($data);
    //     } catch (\Throwable $th) {
    //         return $this->errorResponse(
    //             [],
    //             Lang::get('error.failed_to_insert_the_data')
    //         );
    //     }
    //     return $this->successfulResponse(
    //         $data,
    //         Lang::get('success.successfully_created', ['value' => __('label.item_availability')])
    //     );
    // }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  string  $bid
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        try {
            $data = app()->make(ItemAvailabilityService::class)->update($request->all());
        } catch (\Throwable $th) {
            return $this->errorResponse(
                [],
                Lang::get('error.failed_to_update_the_data')
            );
        }

        Log::info(env('QUEUE_CONNECTION', 'sync'));
        broadcast(new MessageEvent($request->all()));
        return $this->successfulResponse(
            [],
            Lang::get('success.value_successfully_updated', ['value' => __('label.item_availability')])
        );
    }

    // public function siriusPosDataOne()
    // {
    //     $data = [
    //         'bid' => '1000000000000000001',
    //         'device_type' => DeviceType::SIRIUS_POS,
    //         'name' => 'Terminal 1',
    //         'ip_address' => '192.168.1.1',
    //         'products' => [
    //             [
    //                 'product_uom_bid' => '1000000000000000001',
    //                 'item_code' => 'EBC001',
    //                 'barcode' => 'EBC001',
    //                 'description' => 'CF1 3CHEESE',
    //                 'long_description' => 'CLASSIC 3 CHEESE FONDUE',
    //                 'category_bid' => '1000000000000000001',
    //                 'is_available' => '1',
    //             ],
    //             [
    //                 'product_uom_bid' => '1000000000000000002',
    //                 'item_code' => 'EBC002',
    //                 'barcode' => 'EBC002',
    //                 'description' => 'CF2 CREAM CHEESE',
    //                 'long_description' => 'WHITE CHEESE FONDUE',
    //                 'category_bid' => '1000000000000000001',
    //                 'is_available' => '1',
    //             ],
    //             [
    //                 'product_uom_bid' => '1000000000000000003',
    //                 'item_code' => 'EBC003',
    //                 'barcode' => 'EBC003',
    //                 'description' => 'A3 SPICY FONDUE',
    //                 'long_description' => 'SPICY PIMENTO & PEPPER JACK FONDUE',
    //                 'category_bid' => '1000000000000000001',
    //                 'is_available' => '1',
    //             ],
    //             [
    //                 'product_uom_bid' => '1000000000000000004',
    //                 'item_code' => 'EBC004',
    //                 'barcode' => 'EBC004',
    //                 'description' => 'GC5 COLBY',
    //                 'long_description' => 'COLBY JACK WITH BACON GRILLED CHEESE',
    //                 'category_bid' => '1000000000000000001',
    //                 'is_available' => '1',
    //             ],
    //             [
    //                 'product_uom_bid' => '1000000000000000005',
    //                 'item_code' => 'EBC005',
    //                 'barcode' => 'EBC005',
    //                 'description' => 'GC3 BUF CRM',
    //                 'long_description' => 'CREAM CHEESE & BUFFALO CHICKEN GRILLED CHEESE',
    //                 'category_bid' => '1000000000000000001',
    //                 'is_available' => '0',
    //             ],
    //         ]
    //     ];

    //     return $data;
    // }

    // public function siriusPosDataTwo()
    // {
    //     $data = [
    //         'bid' => '1000000000000000002',
    //         'device_type' => DeviceType::SIRIUS_POS,
    //         'name' => 'Terminal 4',
    //         'ip_address' => '192.168.0.4',
    //         'products' => [
    //             [
    //                 'product_uom_bid' => '1000000000000000001',
    //                 'item_code' => 'EBC001',
    //                 'barcode' => 'EBC001',
    //                 'description' => 'CF1 3CHEESE',
    //                 'long_description' => 'CLASSIC 3 CHEESE FONDUE',
    //                 'is_available' => '0',
    //             ],
    //             [
    //                 'product_uom_bid' => '1000000000000000002',
    //                 'item_code' => 'EBC002',
    //                 'barcode' => 'EBC002',
    //                 'description' => 'CF2 CREAM CHEESE',
    //                 'long_description' => 'WHITE CHEESE FONDUE',
    //                 'is_available' => '0',
    //             ],
    //             [
    //                 'product_uom_bid' => '1000000000000000003',
    //                 'item_code' => 'EBC003',
    //                 'barcode' => 'EBC003',
    //                 'description' => 'A3 SPICY FONDUE',
    //                 'long_description' => 'SPICY PIMENTO & PEPPER JACK FONDUE',
    //                 'is_available' => '1',
    //             ],
    //             [
    //                 'product_uom_bid' => '1000000000000000004',
    //                 'item_code' => 'EBC004',
    //                 'barcode' => 'EBC004',
    //                 'description' => 'GC5 COLBY',
    //                 'long_description' => 'COLBY JACK WITH BACON GRILLED CHEESE',
    //                 'is_available' => '1',
    //             ],
    //             [
    //                 'product_uom_bid' => '1000000000000000005',
    //                 'item_code' => 'EBC005',
    //                 'barcode' => 'EBC005',
    //                 'description' => 'GC3 BUF CRM',
    //                 'long_description' => 'CREAM CHEESE & BUFFALO CHICKEN GRILLED CHEESE',
    //                 'is_available' => '1',
    //             ],
    //         ]
    //     ];

    //     return $data;
    // }

    // public function kioskPosDataOne()
    // {
    //     $data = [
    //         'bid' => '1000000000000000003',
    //         'device_type' => DeviceType::KIOSK,
    //         'name' => 'Terminal 3',
    //         'ip_address' => '192.168.0.0',
    //         'products' => [
    //             [
    //                 'product_uom_bid' => '1000000000000000001',
    //                 'item_code' => 'EBC001',
    //                 'barcode' => 'EBC001',
    //                 'description' => 'CF1 3CHEESE',
    //                 'long_description' => 'CLASSIC 3 CHEESE FONDUE',
    //                 'is_available' => '1',
    //             ],
    //             [
    //                 'product_uom_bid' => '1000000000000000002',
    //                 'item_code' => 'EBC002',
    //                 'barcode' => 'EBC002',
    //                 'description' => 'CF2 CREAM CHEESE',
    //                 'long_description' => 'WHITE CHEESE FONDUE',
    //                 'is_available' => '1',
    //             ],
    //             [
    //                 'product_uom_bid' => '1000000000000000003',
    //                 'item_code' => 'EBC003',
    //                 'barcode' => 'EBC003',
    //                 'description' => 'A3 SPICY FONDUE',
    //                 'long_description' => 'SPICY PIMENTO & PEPPER JACK FONDUE',
    //                 'is_available' => '0',
    //             ],
    //             [
    //                 'product_uom_bid' => '1000000000000000004',
    //                 'item_code' => 'EBC004',
    //                 'barcode' => 'EBC004',
    //                 'description' => 'GC5 COLBY',
    //                 'long_description' => 'COLBY JACK WITH BACON GRILLED CHEESE',
    //                 'is_available' => '0',
    //             ],
    //             [
    //                 'product_uom_bid' => '1000000000000000005',
    //                 'item_code' => 'EBC0051',
    //                 'barcode' => 'EBC005',
    //                 'description' => 'GC3 BUF CRM',
    //                 'long_description' => 'CREAM CHEESE & BUFFALO CHICKEN GRILLED CHEESE',
    //                 'is_available' => '0',
    //             ],
    //         ]
    //     ];

    //     return $data;
    // }

    public function getTerminals($data)
    {
        $header = [];
        $detail = [];

        foreach ($data as $item) {
            $item = (object) $item;
            $filters = (object) ['device_type' => $item->device_type];
            $terminal = app()->make(DeviceSettingsRepository::class)->list($filters, false)->toArray();

            $header[] = [
                'name' => $item->device_type,
                'terminals' => $terminal
            ];

            foreach ($terminal as $data) {
                $detail[] = $data;
            }
        }

        return ['header' => $header, 'terminals' => $detail];
    }
}
