<?php

namespace App\Transformers;

use App\Entities\CDISKitchenDevicePrinter;
use League\Fractal\TransformerAbstract;

class KitchenPrinterTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        //
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        //
    ];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(CDISKitchenDevicePrinter $model)
    {
        return [
            'bid' => (string) $model->bid,
            'code' => $model->code,
            'description' => $model->description,
            'device_printer' => $model->device_printer,
            'printer_host' => $model->printer_host,
            'local_printer' => $model->local_printer,
            'status' => (int) $model->status,
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at
        ];
    }
}
