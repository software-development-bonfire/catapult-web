<?php

namespace App\Repositories\Eloquent;

use App\Entities\DeviceSettings;
use App\Enums\API\DeviceType;
use App\Enums\Status;
use App\Repositories\Contracts\DeviceSettingsRepository;
use Illuminate\Support\Facades\DB;
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
        $filters = (object) $filters;
        
        $this->model = $this->model
            ->select([
                // 'device_settings.*',
                DB::raw('device_settings.bid as bid'),
                DB::raw('device_settings.terminal_code as terminal_code'),
                DB::raw('device_settings.device_code as device_code'),
                DB::raw('device_settings.device_uid as device_uid'),
                DB::raw('device_settings.device_type as device_type'),
                DB::raw('device_settings.name as name'),
                DB::raw('device_settings.ip_address as ip_address'),
                DB::raw('device_settings.socket_status as socket_status'),
                DB::raw('device_settings.print_invoice as print_invoice'),
                DB::raw('device_settings.background_process_priority as background_process_priority'),
                DB::raw('device_settings.token as token'),
                DB::raw('device_settings.status as status'),
                DB::raw('device_settings.deleted_at as deleted_at'),
                DB::raw('cdis_kitchen_station.bid as kitchen_station_bid'),
                DB::raw('cdis_kitchen_station.name as kitchen_station_name')
            ])
            ->leftJoin('cdis_kitchen_station', 'cdis_kitchen_station.bid', '=', 'device_settings.kitchen_station_bid');

        if (isset($filters->device_type) && $filters->device_type != '') {
            $this->model->where('device_settings.device_type', $filters->device_type)
                ->where('device_settings.status', Status::ACTIVE)
                ->orderBy('device_settings.name', 'asc');
        }

        if ($isForHeader) {
            $this->model->where('device_settings.status', Status::ACTIVE)
                ->whereIn('device_settings.device_type', [DeviceType::KIOSK, DeviceType::SIRIUS_POS])
                ->groupBy('device_settings.device_type');
        }

        return $isForHeader || isset($filters->device_type) ? $this->model->get() : $this->paginate($filters['itemsPerPage']);
    }

    public function getActivePOS()
    {
        $this->model = $this->model->where('device_settings.device_type', DeviceType::SIRIUS_POS)
            ->where('device_settings.socket_status', Status::ACTIVE)
            ->where('device_settings.status', Status::ACTIVE)
            ->whereNull('device_settings.deleted_at')
            ->orderBy('device_settings.background_process_priority', 'ASC');

        return $this->model->get();
    }

    /**
     * Get kitchen stations
     *
     * @param Object $filters
     * @return Collection $result.
     */
    public function getKitchenStations()
    {
        $this->model =  DB::table('cdis_kitchen_station')
            ->select([
                DB::raw('cdis_kitchen_station.bid as `bid`'),
                DB::raw('cdis_kitchen_station.code as `code`'),
                DB::raw('cdis_kitchen_station.name as `name`'),
            ])
            ->where('cdis_kitchen_station.status', Status::ACTIVE)
            ->whereNull('cdis_kitchen_station.deleted_at')
            ->groupBy(['cdis_kitchen_station.bid']);

        $result = $this->model->get();
        $this->resetModel();

        return $result;
    }


    /**
     * Get kitchen stations
     *
     * @param Object $filters
     * @return Collection $result.
     */
    public function getKitchenStation($filters)
    {
        $this->model = $this->model
            ->select([
                DB::raw('cdis_kitchen_station.bid as bid'),
                DB::raw('cdis_kitchen_station.code as code'),
                DB::raw('cdis_kitchen_station.name as name'),
                DB::raw('cdis_kitchen_station.queueing_group_type as queueing_group_type'),
                DB::raw('cdis_kitchen_station.screen_prioritization as screen_prioritization'),
                DB::raw('cdis_kitchen_station.status as status')
            ])
            ->leftJoin('cdis_kitchen_station', 'cdis_kitchen_station.bid', '=', 'device_settings.kitchen_station_bid')
            ->where('device_settings.status', Status::ACTIVE)
            ->where('device_settings.device_uid', $filters->device_uid)
            ->whereNull('device_settings.deleted_at')

            ->where('cdis_kitchen_station.status', Status::ACTIVE)
            ->whereNull('cdis_kitchen_station.deleted_at')
            ->groupBy(['cdis_kitchen_station.bid']);

        $result = $this->model->first();

        return $result;
    }
}
