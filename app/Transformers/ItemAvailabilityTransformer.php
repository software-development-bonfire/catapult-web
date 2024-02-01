<?php

namespace App\Transformers;

use App\Entities\ItemAvailability;
use App\Entities\DeviceSettings;
use App\Repositories\Contracts\DeviceSettingsRepository;
use App\Repositories\Contracts\CDISProductCategoryRepository;
use App\Enums\Status;
use League\Fractal\TransformerAbstract;

class ItemAvailabilityTransformer extends TransformerAbstract
{
    private $terminals;
    private $categories;

    public function __construct($terminals)
    {
        $this->terminals = $terminals;
        $this->categories = [];
    }

    /**
     * A Fractal transformer.
     *
     * @param  DeviceSettings $model
     * @return array
     */
    public function transform(ItemAvailability $model)
    {
        $data = [
            'bid' => $model->bid,
            'product_uom_bid' => $model->product_uom_bid,
            'item_code' => $model->item_code,
            'barcode' => $model->barcode,
            'description' => $model->description,
            'long_description' => $model->long_description,
            'category_bid' => $model->category_bid,
            'categories' => $this->setCategory($model->category),
            'devices' => $this->setAvailability($model->product_uom_bid, $model->itemAvailabilityDetail)
        ];

        return $data;
    }

    /**
     * set category
     *
     * @return \Illuminate\Http\Response
     */
    public function setCategory($data)
    {
        if ($data['level'] != 0) {
            (object) $productCategories = app()->make(CDISProductCategoryRepository::class)
                ->where('bid', $data['parent_bid'])
                ->where('level', $data['level'] - 1)
                ->first();

            if ($productCategories['level'] == 0) {
                $this->categories['category'] = $productCategories['name'];
                $this->categories['sub_category_1'] = $data['name'];
            } else {

                $this->categories['sub_category_2'] = $data['name'];
                $this->setCategory($productCategories);
            }
        } else {
            $this->categories['category'] = $data['name'];
        }

        return $this->categories;
    }

    public function setAvailability($productUomBid, $detail)
    {
        $data = [];

        foreach ($this->terminals as $device) {
            $device = (object) $device;
            $data[] = ! $this->getAvailabilityDetail($device->bid, $detail)
                ? ['device_detail' => $device, 'item_availability_detail_bid' => null, 'is_available' => 0]
                : $this->getAvailabilityDetail($device->bid, $detail);
        }

        return $data;
    }

    public function getAvailabilityDetail($deviceBid, $detail)
    {
        $data = [];
        foreach ($detail as $item) {
            $item = (object) $item;
            if ($item->device_settings_bid == $deviceBid) {
                $data['device_detail'] = $item->device;
                $data['item_availability_detail_bid'] = $item->bid;
                $data['is_available'] = $item->is_available;
            }
        }

        return $data;
    }
}
