<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatapultDbSetupRequest extends FormRequest
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
            'name' => ['required', 'max:45', Rule::unique('catapult_db_setups')->ignore($this->bid)->where(
                function ($query) {
                    $query->where('deleted_at', null);
                }
            )],
            'host' => 'required|max:45',
            'port' => 'required|max:45',
            'db_name' => 'required|max:45',
            'username' => 'required|max:45',
            'password' => 'required_without:id|max:128',
            'status' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('validation.required', [ 'attribute' => __('label.catapult_db_setup_name') ]),
            'host.required' => __('validation.required', [ 'attribute' => __('label.host') ]),
            'port.required' => __('validation.required', [ 'attribute' => __('label.port') ]),
            'db_name.required' => __('validation.required', [ 'attribute' => __('label.db_name') ]),
            'username.required' => __('validation.required', [ 'attribute' => __('label.username') ]),
            'password.required_without' => __('validation.required', [ 'attribute' => __('label.password') ]),
            'status.required' => __('validation.required', [ 'attribute' => __('label.status') ]),
        ];
    }
}
