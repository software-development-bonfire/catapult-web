<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FieldMappingRequest extends FormRequest
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
                    'api_endpoint' => 'sometimes',
                    'name' => [
                        'required',
                        'max:45',
                        'unique:field_mapping,name,NULL,bid,deleted_at,NULL'
                    ],
                    'type' => 'required',
                    'status' => 'required',
                    'remote_setup_bid' => 'required',
                    'catapult_db_setup_bid' => 'required',
                    'api_setup_bid' => 'required'
                ];

                if ($this->method() == 'PATCH') {
                    unset($rules['name']);

                    $rules['name'] = [
                        'required',
                        'max:45',
                        'unique:field_mapping,name,'.$this->bid.',bid,deleted_at,NULL'
                    ];
                }
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => __('validation.required', [ 'attribute' => __('label.field_mapping_name') ]),
            'name.unique' => __('validation.unique', [ 'attribute' => __('label.field_mapping_name') ]),
            'type.required' => __('validation.required', [ 'attribute' => __('label.mapping_type') ]),
            'status.required' => __('validation.required', [ 'attribute' => __('label.setup_status') ]),
            'remote_setup_bid.required' => __('validation.required', [ 'attribute' => __('label.remote_setup_name') ]),
            'catapult_db_setup_bid.required' => __('validation.required', [ 'attribute' => __('label.catapult_db_setup_name') ]),
            'api_setup_bid.required' => __('validation.required', [ 'attribute' => __('label.api_setup_name') ]),
        ];
    }
}
