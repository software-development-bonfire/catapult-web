<?php

namespace App\Http\Requests;

use App\Enums\StorageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FileStorageSetupRequest extends FormRequest
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
        $storageType = $this->get('storage_type');

        $rules = [
            'name' => 'required|max:45|unique:file_storage_setup,name,NULL,bid,deleted_at,NULL',
            'storage_type' => 'required',
            'local_path' => 'required|max:254',
            'remote_path' => 'required|max:254',
            'status' => 'required',
            'username' => 'nullable|max:45',
            'password' => 'nullable|max:128',
        ];

        if ($storageType == StorageType::FTP) {
            $rules['server'] = 'required|max:45';
            $rules['host'] = 'required|max:45';
            $rules['port'] = 'required|max:45';
            $rules['username'] = 'required|max:45';
            $rules['password'] = 'required_without:id|max:128';
        }

        if ($this->method() == 'PATCH') {
            unset($rules['name']);
            $rules['name'] = 'required|max:45|unique:file_storage_setup,name,'.$this->bid.',bid,deleted_at,NULL';
        }

        return $rules;
    }

    public function messages()
    {
        $storageType = $this->get('storage_type');

        return [
            'name.required' => __('validation.required', [ 'attribute' => __('label.name') ]),
            'storage_type.required' => __('validation.required', [ 'attribute' => __('label.storage_type') ]),
            'local_path.required' => __('validation.required', [ 'attribute' =>__('label.local_path') ]),
            'remote_path.required' => __('validation.required', [ 'attribute' =>__('label.remote_path') ]),
            'server.required' => __('validation.required', [ 'attribute' => __('label.remote_server') ]),
            'host.required' => __('validation.required', [ 'attribute' => __('label.host') ]),
            'port.required' => __('validation.required', [ 'attribute' => __('label.port') ]),
            'username.required' => __('validation.required', [
                'attribute' =>
                    $storageType === StorageType::LOCAL_NETWORK
                        ? __('label.username')
                        : __('label.remote_username')
            ]),
            'password.required_without' => __('validation.required', [
                'attribute' =>
                    $storageType === StorageType::LOCAL_NETWORK
                        ? __('label.password')
                        : __('label.remote_password')
            ]),
            'status.required' => __('validation.required', [ 'attribute' => __('label.status') ]),
        ];
    }
}
