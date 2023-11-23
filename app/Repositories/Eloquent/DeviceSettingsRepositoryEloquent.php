<?php

namespace App\Repositories\Eloquent;

use App\Entities\DeviceSettings;
use App\Repositories\Contracts\DeviceSettingsRepository;
use Illuminate\Support\Facades\DB;

class DeviceSettingsRepositoryEloquent extends BaseEloquent implements DeviceSettingsRepository
{
    public function model()
    {
        return DeviceSettings::class;
    }

    /**
     * Get list
     *
     * @param Object $filters
     * @return Collection $result.
     */
    public function list($filters = null)
    {
        $this->model = $this->model->select('*');
        return $this->paginate($filters['itemsPerPage']);
    }
}
