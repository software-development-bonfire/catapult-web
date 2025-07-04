<?php

namespace App\Transformers;

use App\Entities\POSTerminalTransaction;
use League\Fractal\TransformerAbstract;
use App\Enums\API\DeviceType;
use App\Enums\PaymentStatus;
use App\Enums\Type;
use App\Enums\Status;

class BranchUniversalOrderSummaryTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @param POSTerminalTransaction $model
     * @return array
     */
    public function transform(POSTerminalTransaction $model)
    {
        return [
            'bid' => $model->bid,
            'order_no' => $model->or_number,
            'date_and_time' => date("F d, Y H:i:s", strtotime($model->log_date)),
            'time_needed' => $model->order_schedule ? date("F d, Y H:i:s", strtotime($model->order_schedule)) : date("F d, Y H:i:s"),
            'ordertaker_id' => DeviceType::getDescription($model->device_type),
            'order_reference' => $model->order_number,
            'type' => $model->type,
            'payment' => PaymentStatus::getDescription($model->payment_status).' - '.DeviceType::getDescription($model->device_type),
            'process_in' => $model->terminal_name,
            'status' => $model->status,
        ];
    }
}
