<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApiSetupRequest extends FormRequest
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
            'name' => ['required', 'max:45', Rule::unique('api_setups')->ignore($this->bid)->where(
                function ($query) {
                    $query->where('deleted_at', null);
                }
            )],
            'end_point' => 'required|max:128',
            'status' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('validation.required', [ 'attribute' => __('label.api_name') ]),
            'end_point.required' => __('validation.required', [ 'attribute' => __('label.end_point') ]),
            'status.required' => __('validation.required', [ 'attribute' => __('label.status') ]),
        ];
    }
}
