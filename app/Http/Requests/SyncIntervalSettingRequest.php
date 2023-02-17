<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncIntervalSettingRequest extends FormRequest
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
            'type' => 'sometimes',
            'name' => ['required', 'max:45', Rule::unique('sync_interval_settings')->ignore($this->bid)
                ->where(function ($query) {
                    $query->where('deleted_at', null);
                }
            )],
            'checking_interval' => 'required',
            'syncing_type' => 'required',
            'start_time' => ['required_if:checking_interval,"End of Day"', 'max:45'],
            'status' => 'required',
        ];
    }
}
