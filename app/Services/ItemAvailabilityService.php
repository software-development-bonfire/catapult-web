<?php

namespace App\Services;

use App\Entities\ItemAvailability;
use App\Entities\DeviceSettings;
use App\Entities\ItemAvailabilityDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Traits\DatabaseTransaction;

class ItemAvailabilityService
{
    use DatabaseTransaction;
    /**
     * Store data
     *
     * @param array $data
     */
    public function store($data)
    {
        return $this->transaction(function () use ($data) {
            foreach ($data as $header) {
                $deviceSettings = DeviceSettings::where('device_type', $header['device_type'])
                    //->where('name', $header['name'])
                    ->where('terminal_code', $header['terminal_code'])
                    ->where('device_code', $header['device_code'])
                    ->first();

                if (! $deviceSettings) {
                    $deviceSettings = DeviceSettings::create([
                        'device_type' =>  $header['device_type'],
                        'terminal_code' =>  $header['terminal_code'],
                        'device_code' =>  $header['device_code'],
                        'device_uid' =>  $header['device_uid'],
                        'name' =>  $header['name'],
                        'ip_address' => $header['ip_address'],
                    ]);
                }

                    foreach ($header['products'] as $product) {

                        $item = ItemAvailability::where('product_uom_bid', $product['product_uom_bid'])
                            ->where('item_code', $product['item_code'])
                            ->where('barcode', $product['barcode'])
                            ->where('description', $product['description'])
                            ->where('long_description', $product['long_description'])
                            ->where('category_bid', $product['category_bid'])
                            ->first();

                        if (! $item) {
                            $itemAvailability = ItemAvailability::create([
                                'product_uom_bid' => $product['product_uom_bid'],
                                'item_code' => $product['item_code'],
                                'barcode' => $product['barcode'],
                                'description' => $product['description'],
                                'long_description' => $product['long_description'],
                                'category_bid' => $product['category_bid']
                            ]);

                            if ($itemAvailability) {
                                $itemAvailability->detail()->create([
                                    'head_bid' => $itemAvailability->bid,
                                    'device_settings_bid' => $deviceSettings->bid,
                                    'is_available' => $product['is_available']
                                ]);
                            }
                        } else {
                            $itemDetail = ItemAvailabilityDetail::where('head_bid', $item->bid)->where('device_settings_bid', $deviceSettings->bid)->first();

                            if (! $itemDetail) {
                                ItemAvailabilityDetail::create([
                                    'head_bid' => $item->bid,
                                    'device_settings_bid' => $deviceSettings->bid,
                                    'is_available' => $product['is_available'],
                                ]);
                            } else {
                                ItemAvailabilityDetail::where('head_bid', $item->bid)->where('device_settings_bid', $deviceSettings->bid)
                                    ->update(['is_available' => $product['is_available']]);
                            }
                        }
                    }
            }

            return true;
        });
    }

    /**
     * Update data
     *
     * @param array $data
     * @param string $bid
     */
    public function update($data)
    {
        $isAvailable = $data['is_available'] == 1 ? 0 : 1;

        $data['updated_by'] = Auth::user()->bid;
        $result = ItemAvailabilityDetail::find($data['item_availability_detail_bid'])->update(['is_available' => $data['is_available']]);
        return $result;
    }
}