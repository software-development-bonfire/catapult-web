<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeviceSettingsRequest extends FormRequest
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

        switch($requestMethod) {
            case 'PATCH':
            case 'POST':
                $rules = [
                    'bid' => 'sometimes',
                    'device_type' => 'required',
                    'name' => [
                        'required',
                        'max:45',
                        'unique:device_settings,name,NULL,bid,deleted_at,NULL'
                    ],
                    'ip_address' => [
                        'required',
                        'ip',
                        'unique:device_settings,ip_address,NULL,bid,deleted_at,NULL'
                    ],
                    'api_endpoint' => [
                        'required',
                        'unique:device_settings,api_endpoint,NULL,bid,deleted_at,NULL'
                    ],
                    'token' => [
                        'required',
                        'unique:device_settings,token,NULL,bid,deleted_at,NULL'
                    ],
                    'status' => 'required',
                ];

                if ($this->method() == 'PATCH') {
                    unset($rules['name']);
                    unset($rules['ip_address']);
                    unset($rules['api_endpoint']);
                    unset($rules['token']);

                    $rules['name'] = [
                        'required',
                        'max:45',
                        'unique:device_settings,name,'.$bid.',bid,deleted_at,NULL'
                    ];

                    $rules['ip_address'] = [
                        'required',
                        'ip',
                        'unique:device_settings,ip_address,'.$bid.',bid,deleted_at,NULL'
                    ];

                    $rules['api_endpoint'] = [
                        'required',
                        'unique:device_settings,api_endpoint,'.$bid.',bid,deleted_at,NULL'
                    ];

                    $rules['token'] = [
                        'required',
                        'unique:device_settings,token,'.$bid.',bid,deleted_at,NULL'
                    ];
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
            'name.required' => __('error.value_is_required', ['value' => __('label.device_name')]),
            'name.unique' => __('error.value_has_already_been_taken', ['value' => __('label.device_name')]),
            'device_type.required' => __('error.please_select_value', ['value' => __('label.device_type')]),
            'ip_address.required' => __('error.value_is_required', ['value' => __('label.ip_address')]),
            'ip_address.unique' => __('error.value_has_already_been_taken', ['value' => __('label.ip_address')]),
            'ip_address.ip' => __('error.please_enter_a_valid_value', ['value' => __('label.ip_address')]),
            'api_endpoint.required' => __('error.value_is_required', ['value' => __('label.api_endpoint')]),
            'api_endpoint.unique' => __('error.value_has_already_been_taken', ['value' => __('label.api_endpoint')]),
            'token.required' => __('error.value_is_required', ['value' => __('label.token')]),
            'token.unique' => __('error.value_has_already_been_taken', ['value' => __('label.token')]),
            'status.required' => __('validation.required', [ 'attribute' => __('label.status') ]),
        ];
    }
}
