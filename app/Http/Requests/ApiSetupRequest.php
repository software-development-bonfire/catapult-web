<?php

namespace App\Http\Requests;

use App\Rules\Lowercase;
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
        $rules = [];

        $requestMethod = $this->method();

        switch($requestMethod) {
            case 'PATCH':
            case 'POST':
                $rules = [
                    'name' => ['required', 'max:45', 'unique:api_setups,name,NULL,bid,deleted_at,NULL'],
                    'end_point' => 'required|max:128',
                    'status' => 'required',
                ];

                if ($this->method() == 'PATCH') {
                    unset($rules['field']);

                    $rules['name'] = [
                        'required',
                        'max:45',
                        'unique:api_setups,name,'.$this->bid.',bid,deleted_at,NULL'
                    ];
                }
        }

        return $rules;;
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
