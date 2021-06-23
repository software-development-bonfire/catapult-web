<?php

namespace App\Http\Requests;

use App\Entities\FieldMapping;
use App\Rules\Lowercase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'api_version_name' => ['required', 'max:128', Rule::unique('field_mappings')->ignore($this->bid)->where(
                function ($query) {
                    $query->where(
                        ['deleted_at' => null, 'type' => $this->type, 'api_endpoint' => $this->api_endpoint]
                    );
                }
            )],
            'status' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'api_version_name.required' => __('validation.required', [ 'attribute' => __('label.api_version_name') ]),
        ];
    }
}
