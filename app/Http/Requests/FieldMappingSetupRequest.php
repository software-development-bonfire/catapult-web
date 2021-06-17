<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FieldMappingSetupRequest extends FormRequest
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
            'type' => 'required',
            'api_endpoint' => 'required|max:45',
            'api_version_name' => 'required|max:128',
            'status' => 'required',
            'details' => 'required_if:bid, null'
        ];
    }

    public function messages()
    {
        return [
            'api_version_name.required' => __('validation.required', [ 'attribute' => __('label.api_version_name') ]),
            'details.required' =>  __('validation.field_mapping_cdis_required'),
        ];
    }
}
