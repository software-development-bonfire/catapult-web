<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RemoteSetupRequest extends FormRequest
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
            'name' => ['required', 'max:45', Rule::unique('remote_setups')->ignore($this->id)],
            'path' => 'required|max:45',
            'server' => 'required|max:45',
            'host' => 'required|max:45',
            'port' => 'required|max:45',
            'username' => 'required|max:45',
            'password' => 'required_without:id|max:128',
            'remarks' => 'sometimes',
            'status' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('validation.required', [ 'attribute' => __('label.remote_name') ]),
            'path.required' => __('validation.required', [ 'attribute' => __('label.remote_path') ]),
            'server.required' => __('validation.required', [ 'attribute' => __('label.remote_server') ]),
            'host.required' => __('validation.required', [ 'attribute' => __('label.remote_host') ]),
            'port.required' => __('validation.required', [ 'attribute' => __('label.remote_port') ]),
            'username.required' => __('validation.required', [ 'attribute' => __('label.remote_username') ]),
            'password.required_without' => __('validation.required', [ 'attribute' => __('label.remote_password') ]),
            'status.required' => __('validation.required', [ 'attribute' => __('label.remote_status') ]),
        ];
    }
}
