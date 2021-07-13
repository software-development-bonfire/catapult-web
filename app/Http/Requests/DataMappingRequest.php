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
        return [
            'bid' => 'sometimes',
            'mapping_type' => 'sometimes', 
            'end_point' => 'required',
            'field_mapping_bid' => 'required',
            'field_mapping_list_bid' => 'required',
            'api_version_name' => 'required',
            'fields' => 'required',
            'fields.*.required' => 'required',
            'fields.*.field' => 'required',
            'fields.*.description' => 'sometimes',
            'fields.*.mapping_type' => 'required',
            'fields.*.file_name' => 'required_if:mapping_type,2',
            'fields.*.default_value' => 'sometimes',
            'fields.*.column_name' => 'required_if:fields.*.required,true',
        ];
    }

    public function messages()
    {
        return [
            'end_point.required' => __('validation.required', [ 'attribute' => __('label.api_endpoint') ]),
            'api_version_name.required' => __('validation.required', [ 'attribute' => __('label.api_version_name') ]),
            'field_mapping_list_bid.required' => __('validation.required', [ 'attribute' => __('label.field_mapping_name') ]),
            'fields.required' => __('validation.required', [ 'attribute' => __('label.cdis_field') ]),
            'fields.*.file_name.required_if' => __('validation.required', [ 'attribute' => __('label.csv_file_name_identifier')]),
            'fields.*.column_name.required_if' => __('validation.required', [ 'attribute' => __('label.csv_column_name')]),
        ];
    }
}
