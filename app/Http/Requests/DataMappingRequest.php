<?php

namespace App\Http\Requests;

use App\Rules\Test;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Lang;

class DataMappingRequest extends FormRequest
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
        $rules = [
            'bid' => 'sometimes',
            'mapping_type' => 'sometimes',
            'data_entry' => 'required',
            'field_mapping_bid' => 'required',
            'fields' => 'required',
            'fields.*.required' => 'required',
            'fields.*.field' => 'required',
            'fields.*.description' => 'sometimes',
            'fields.*.mapping_type' => 'required',
            'fields.*.file_name' => 'required_if:mapping_type,2',
            'fields.*.default_value' => 'sometimes',
            'fields.*.column_name' => 'required_if:fields.*.required,true',
        ];

        if ($this->is_customized_mapping) {
            $rules['primary_table'] = 'required';
            unset($rules['fields.*.field']);
            unset($rules['fields.*.description']);
            unset($rules['fields.*.mapping_type']);
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'data_entry.required' => __('validation.required', [ 'attribute' => __('label.data_entry') ]),
            'preset_name.required' => __('validation.required', [ 'attribute' => __('label.preset_name') ]),
            'field_mapping_list_bid.required' => __('validation.required', [ 'attribute' => __('label.field_mapping_name') ]),
            'fields.required' => __('validation.required', [ 'attribute' => __('label.cdis_field') ]),
            'fields.*.file_name.required_if' => __('validation.required', [ 'attribute' => __('label.csv_file_name_identifier')]),
            'fields.*.column_name.required_if' => __('validation.required', [ 'attribute' => __('label.csv_column_name')]),
        ];
    }
}
