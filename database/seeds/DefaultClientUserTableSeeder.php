<?php

use App\Enums\Permissions;
use App\Enums\Status;
use App\Enums\UserType;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DefaultClientUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $clientUser =  [
            'name' => 'Catapult User',
            'username' => 'catapult',
            'password' => bcrypt('catapult'),
            'status' => Status::ACTIVE,
            'type' => UserType::DEFAULT
        ];

        $user = User::whereUsername('catapult')->first();

        if (! $user) {
            User::create($clientUser);
        }

        $user = User::whereUsername('catapult')->first();
        $user->permissions()->delete();

        $viewPermissions = collect(Permissions::LIST)->only('view')->toArray()['view'];
        $userPermissions = collect($viewPermissions)->only('dashboard')->toArray();

        foreach ($userPermissions as $permission) {
            $user->permissions()->create(array(
                'code' => $permission,
                'updated_by' => 1,
            ));
        }
    }
}
