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
        return [
            'bid' => 'sometimes',
            'field_mapping_bid' => 'sometimes',
            'required' => 'required',
            'field' => ['required', 'max:45', new Lowercase, Rule::unique('field_mapping_details')->ignore($this->bid)
            ->where(
                function ($query) {
                    $query->where('field_mapping_bid', $this->field_mapping_bid);
                }
            )],
            'description' => 'sometimes|max:128',
            'mapping_type' => 'required',
            'file_name' => 'sometimes',
            'default_value' => 'sometimes',
            'column_name' => 'sometimes',
        ];
    }

    public function messages()
    {
        return [
            'field.required' => __('validation.required', [ 'attribute' => __('label.cdis_field') ]),
            'field.unique' => __('validation.unique', [ 'attribute' => __('label.cdis_field') ]),
        ];
    }
}
