<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KitchenPrinterRequest extends FormRequest
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
            'local_printer' => 'required',
        ];
        return $rules;
    }
    public function messages()
    {
        return [
            'permission.required' => __('validation.user_should_have_atleast_one_permission'),
            'cdis_kitchen_device_printer.local_printer.required' => __('validation.required', ['attribute' => __('label.local_printer')]),
        ];
    }
}
