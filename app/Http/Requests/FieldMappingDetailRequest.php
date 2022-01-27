<?php

namespace App\Http\Requests;

use App\Rules\Lowercase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FieldMappingDetailRequest extends FormRequest
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
                    'field_mapping_bid' => 'sometimes',
                    'required' => 'required',
                    'field' => [
                        'required',
                        'max:45',
                        new Lowercase,
                        'unique:data_mappings,field,NULL,bid,field_mapping_list_bid,'.$this->head['bid']
                    ],
                    'description' => 'sometimes|max:128',
                    'mapping_type' => 'required',
                    'file_name' => 'sometimes',
                    'default_value' => 'sometimes',
                    'column_name' => 'sometimes',
                ];

                if ($this->method() == 'PATCH') {
                    unset($rules['field']);

                    $rules['field'] = [
                        'required',
                        'max:45',
                        new Lowercase,
                        'unique:data_mappings,field,'.$this->bid.',bid,field_mapping_list_bid,'.$this->head['bid']
                    ];
                }
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'field.required' => __('validation.required', [ 'attribute' => __('label.cdis_field') ]),
            'field.unique' => __('validation.unique', [ 'attribute' => __('label.cdis_field') ]),
        ];
    }
}
