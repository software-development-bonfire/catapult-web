<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FieldMappingListRequest extends FormRequest
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
            'api_endpoint' => 'sometimes',
            'name' => ['required', 'max:45', Rule::unique('field_mapping_lists')->ignore($this->bid)->where(
                function ($query) {
                    $query->where('deleted_at', null)->where('bid' , '!=', $this->bid);
                })],
            'type' => 'required',
            'status' => 'required',
            'remote_setup_bid' => 'required',
            'catapult_db_setup_bid' => 'required',
            'api_setup_bid' => 'required'
        ];
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
