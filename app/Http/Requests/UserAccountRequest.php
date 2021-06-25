<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserAccountRequest extends FormRequest
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
            'bid' => 'sometimes',
            'status' => 'required',
            'name' => 'required|max:45|regex:/^(?!\d+$)(?:[ñÑa-zA-Z0-9][ñÑa-zA-Z0-9 @&$.,]*)?$/',
            'username' => ['required', 'max:15', Rule::unique('users')->ignore($this->bid)->where(function ($query) {
                $query->where('deleted_at', null);
            })],
            'password' => 'required|required_with:retype_password|same:retype_password|min:8|regex:/[0-9]/|regex:/[@$!%*#?&]/|regex:/[A-Z]/',
            'retype_password' => 'required|required_with:password|same:password|min:8|regex:/[0-9]/|regex:/[@$!%*#?&]/|regex:/[A-Z]/',
            'permission' => 'required'
        ];
        if ($this->mode == "update") {
            unset($rules['password']);
            unset($rules['retype_password']);
            unset($rules['username']);
            
            $rules['username'] = 'required|unique:users,username,' . $this->bid . ',bid,deleted_at,NULL|max:15';
            if ($this->password) {
                $rules['password'] = 'sometimes|same:retype_password|min:8|regex:/[0-9]/|regex:/[@$!%*#?&]/|regex:/[A-Z]/';
                $rules['retype_password'] = 'sometimes|same:password|min:8|regex:/[0-9]/|regex:/[@$!%*#?&]/|regex:/[A-Z]/';
            }
        }

        return $rules;
    }
    public function messages()
    {
        return [
            'permission.required' => __('validation.user_should_have_atleast_one_permission'),
            'user.password.required' => __('validation.required', ['attribute' => __('label.password')]),
            'user.password.required_with' => __('validation.required_with', ['attribute' => __('label.password'), 'values' => __('label.retype_password')]),
            'user.password.regex' => __('validation.regex', ['attribute' => __('label.password')]),
            'user.password.same' => __('validation.password_doesnt_match'),
        ];
    }
}
