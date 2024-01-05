<?php

namespace App\Http\Controllers\POS\v1;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ItemAvailabilityRepository;
use App\Services\ItemAvailabilityService;
use App\Transformers\ItemAvailabilityTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class ItemAvailabilityController extends Controller
{

    public function store(Request $request)
    {
        $data = (object) stringToJson($request->all());

        Log::info(json_encode($data));
        $result = app()->make(ItemAvailabilityService::class)->store($data->data);
        return $this->successfulResponse(
            $data,
            Lang::get('success.successfully_created', ['value' => __('label.item_availability')])
        );
    }

    public function list(Request $request) {
        $filters = stringToJson($request->get('filters'));
        $terminals = [];

        $list = app()->make(ItemAvailabilityRepository::class)->list($filters, false);
        $list = fractal($list, new ItemAvailabilityTransformer($terminals));
        return $this->successfulResponse($list->toArray()['data']);
    }
}
