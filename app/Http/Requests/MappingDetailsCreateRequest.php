<?php

namespace App\Http\Requests;

use App\Rules\Lowercase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MappingDetailsCreateRequest extends FormRequest
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
            'api_version_name' => ['required', 'max:128', Rule::unique('field_mappings')->ignore($this->bid)->where(
                function ($query) {
                    $query->where(
                        ['deleted_at' => null, 'type' => $this->type, 'api_endpoint' => $this->api_endpoint]
                    );
                }
            )],
            'status' => 'required',
            'details' => ['required_if:method,create', 'array'],
            'details.*.required' => 'required',
            'details.*.field_mapping_bid' => 'required',
            'details.*.field' => ['required', 'max:45', new Lowercase, Rule::unique('field_mapping_details')->ignore($this->bid)->where(
                function ($query) {
                    $query->where('field_mapping_bid', $this->bid);
                }
            )],
            'details.*.mapping_type' => 'required',
            'details.*.description' => 'sometimes',
            'details.*.file_name' => 'sometimes',
            'details.*.default_value' => 'sometimes',
            'details.*.column_name' => 'sometimes',
        ];
    }

    public function messages()
    {
        return [
            'api_version_name.required' => __('validation.required', [ 'attribute' => __('label.api_version_name') ]),
            'details.required' =>  __('validation.field_mapping_cdis_required'),
            'details.required_if' =>  __('validation.field_mapping_cdis_required'),
            'details.*.field.required' => __('validation.required', [ 'attribute' => __('label.cdis_field') ]),
            'details.*.field.unique' => __('validation.unique', [ 'attribute' => __('label.cdis_field') ]),
        ];
    }
}
