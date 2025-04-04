<?php

namespace App\Http\Requests;

use App\Enums\API\DeviceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeviceSettingsNewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($method = null)
    {
        $rules = [];

        $requestMethod = $this->method();

        if (! is_null($method)) {
            $requestMethod = $method;
        }

        $bid = $this->request->get('bid');
        $deviceType = $this->request->get('device_type');

        switch($requestMethod) {
            case 'PATCH':
            case 'POST':
                $rules = [
                    'bid' => 'sometimes',
                    'status' => 'required',
                ];

                if ($this->method() == 'PATCH') {
                    unset($rules['name']);
                    unset($rules['ip_address']);
                    unset($rules['api_endpoint']);
                    unset($rules['token']);
                    unset($rules['device_uid']);
                    unset($rules['device_code']);
                    unset($rules['terminal_code']);

                    if ($deviceType == DeviceType::SIRIUS_POS) {
                        $rules['background_process_priority'] = [
                            'required',
                            'integer',
                            'unique:device_settings,background_process_priority,'.$bid.',bid,deleted_at,NULL'
                        ];
                    }
                    
                    if ($deviceType == DeviceType::KDS) {
                        $rules['kitchen_station_bid'] = [
                            'required',
                            'max:45',
                            'unique:device_settings,kitchen_station_bid,'.$bid.',bid,deleted_at,NULL'
                        ];
                    }
                }

            break;

            case 'DELETE':
                break;
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'background_process_priority.required' => __('error.value_is_required', ['value' => __('label.background_process_priority')]),
            'background_process_priority.unique' => __('error.value_has_already_been_taken', ['value' => __('label.background_process_priority')]),
            'kitchen_station_bid.required' => __('error.value_is_required', ['value' => __('label.kitchen_station')]),
            'kitchen_station_bid.unique' => __('error.value_has_already_been_taken', ['value' => __('label.kitchen_station')]),
            'status.required' => __('validation.required', [ 'attribute' => __('label.status') ]),
        ];
    }
}
