<?php

namespace App\Repositories\Eloquent;

use App\Entities\DeviceSettings;
use App\Enums\Status;
use App\Repositories\Contracts\DeviceSettingsRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prettus\Repository\Eloquent\BaseRepository;

class DeviceSettingsRepositoryEloquent extends BaseRepository implements DeviceSettingsRepository
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
    public function list($filters, $isForHeader)
    {
        $this->model = $this->model->select('*');

        if (isset($filters->device_type) && $filters->device_type != '') {
            $this->model->where('device_type', $filters->device_type)
                ->where('status', Status::ACTIVE)
                ->orderBy('name', 'asc');
        }

        if ($isForHeader) {
            $this->model->where('status', Status::ACTIVE)
                ->groupBy('device_type');
        }

        return $isForHeader || isset($filters->device_type) ? $this->model->get() : $this->paginate($filters['itemsPerPage']);
    }
}
