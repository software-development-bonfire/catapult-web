<?php

use Illuminate\Database\Seeder;
use App\Enums\API\DeviceType;
use App\Enums\Status;
use App\Entities\DeviceSettings;

class DeviceSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'terminal_code' => null,
            'device_code' => 'WA-001',
            'device_uid' => 'WA-001',
            'device_type' => DeviceType::ECOMMERCE,
            'name' => 'E-Commerce',
            'ip_address' => '-',
            'api_endpoint' => '-',
            'token' => '-',
            'status' => Status::ACTIVE,
            'socket_status' => Status::ACTIVE,
        ];

        DeviceSettings::create($data);
    }
}
