<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Entities\DeviceSettings;
use App\Repositories\Contracts\DeviceSettingsRepository;
use App\Repositories\Contracts\CDISProductCategoryRepository;
use App\Repositories\Contracts\ItemAvailabilityRepository;
use App\Services\ItemAvailabilityService;
use App\Transformers\ItemAvailabilityTransformer;
use App\Transformers\CDISProductCategoryChosenTransformer;
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
        $productCategoriesIndex = app()->make(CDISProductCategoryRepository::class)->where(['level' => 0, 'status' => Status::ACTIVE])->get();
        $productCategories = fractal()
            ->collection($productCategoriesIndex, CDISProductCategoryChosenTransformer::class)
            ->toJson();

        $header = app()->make(DeviceSettingsRepository::class)->list([], true);
        $header = $this->getTerminals($header);
        $header = json_encode($header['header']);

        return view('item-availability.list', compact(
            'header',
            'productCategories'
        ));
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

        $category = $this->setCategory($filters->category);
        $filters->category = (array) $category;

        $list = app()->make(ItemAvailabilityRepository::class)->list($filters, $terminals);
        $list = fractal($list, new ItemAvailabilityTransformer($terminals));
        return $this->successfulResponse(['list' => $list, 'filters' => $filters]);
    }

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

        broadcast(new MessageEvent($request->all()));
        return $this->successfulResponse(
            [],
            Lang::get('success.value_successfully_updated', ['value' => __('label.item_availability')])
        );
    }

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

    public function setCategory($data)
    {
        if ((is_array($data) && count($data) <= 0) || is_string($data) && $data == '') return [];

        $data = explode(' > ', $data);
        $counter = count($data) - 1;

        return $data[$counter];
    }
}
