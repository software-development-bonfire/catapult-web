<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncingSetupRequest extends FormRequest
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
            'syncing_order' => 'required|max:128',
            'pos_to_cdis_entry_limit' => 'required|numeric|max:1000|min:60',
            'cdis_to_pos_entry_limit' => 'required|numeric|max:1000|min:60'
        ];
    }
}
