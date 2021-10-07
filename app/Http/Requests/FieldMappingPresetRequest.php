<?php

namespace App\Http\Requests;

use App\Rules\Lowercase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FieldMappingPresetRequest extends FormRequest
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
                    'type' => 'required',
                    'data_entry' => 'required|max:45',
                    'preset_name' => [
                        'required',
                        'max:128',
                        'unique:field_mapping_preset,preset_name,NULL,bid,deleted_at,NULL'
                    ],
                    'status' => 'required',
                ];

                if ($this->method() == 'PATCH') {
                    unset($rules['preset_name']);

                    $rules['preset_name'] = [
                        'required',
                        'max:128',
                        'unique:field_mapping_preset,preset_name,'.$this->bid.',bid,deleted_at,NULL'
                    ];
                }
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'preset_name.required' => __('validation.required', [ 'attribute' => __('label.preset_name') ]),
        ];
    }
}
