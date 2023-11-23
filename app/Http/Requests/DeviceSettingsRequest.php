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
    public function rules()
    {
        $rules = [];

        $requestMethod = $this->method();

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
                        'unique:device_settings,ip_address,NULL,bid,deleted_at,NULL'
                    ],
                    'api_endpoint' => 'required',
                    'token' => 'required',
                    'status' => 'required',
                ];

                if ($this->method() == 'PATCH') {
                    unset($rules['name']);
                    unset($rules['ip_address']);

                    $rules['name'] = [
                        'required',
                        'max:45',
                        'unique:device_settings,name,'.$this->bid.',bid,deleted_at,NULL'
                    ];

                    $rules['ip_address'] = [
                        'required',
                        'unique:device_settings,ip_address,'.$this->bid.',bid,deleted_at,NULL'
                    ];
                }
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => __('validation.required', [ 'attribute' => __('label.device_name') ]),
            'name.unique' => __('validation.unique', [ 'attribute' => __('label.device_name') ]),
            'device_type.required' => __('validation.required', [ 'attribute' =>  __('error.please_select_value', ['value' => __('label.device_type')]) ]),
            'ip_address.required' => __('validation.required', [ 'attribute' => __('label.setup_ip_address') ]),
            'ip_address.unique' => __('validation.unique', [ 'attribute' => __('label.ip_address') ]),
            'token.required' => __('validation.required', [ 'attribute' => __('label.token') ]),
            'status.required' => __('validation.required', [ 'attribute' => __('label.status') ]),
        ];
    }
}
