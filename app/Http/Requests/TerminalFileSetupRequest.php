<?php

namespace App\Http\Requests;

use App\Enums\StorageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TerminalFileSetupRequest extends FormRequest
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
        $rules = [
            'name' => 'required|max:45|unique:terminal_file_setups,name,NULL,bid,deleted_at,NULL',
            'type' => 'required',
            'name' => 'max:50',
            'terminal_code' => 'required|max:50',
            'terminal_path' => 'required|max:254',
            'api_setup_bid' => 'sometimes',
            'status' => 'required',
        ];

        if ($this->method() == 'PATCH') {
            unset($rules['name']);
            $rules['name'] = 'required|max:45|unique:terminal_file_setups,name,'.$this->bid.',bid,deleted_at,NULL';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('label.name')]),
            'type.required' => __('validation.required', ['attribute' => __('label.type')]),
            'terminal_code.required' => __('validation.required', ['attribute' => __('label.terminal_code')]),
            'terminal_path.required' => __('validation.required', ['attribute' => __('label.terminal_path')]),
            'api_setup_bid.required' => __('validation.required', ['attribute' => __('label.endpoint')]),
            'status.required' => __('validation.required', ['attribute' => __('label.status')]),
        ];
    }
}
