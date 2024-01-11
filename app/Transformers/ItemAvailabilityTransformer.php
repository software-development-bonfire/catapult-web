<?php

namespace App\Transformers;

use App\Entities\ItemAvailability;
use App\Entities\DeviceSettings;
use App\Repositories\Contracts\DeviceSettingsRepository;
use App\Enums\Status;
use League\Fractal\TransformerAbstract;

class ItemAvailabilityTransformer extends TransformerAbstract
{
    private $terminals;

    public function __construct($terminals)
    {
        $this->terminals = $terminals;
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
            'devices' => $this->setAvailability($model->product_uom_bid, $model->itemAvailabilityDetail)
        ];

        return $data;
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
