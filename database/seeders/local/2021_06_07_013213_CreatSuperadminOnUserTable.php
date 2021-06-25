<?php

use App\Enums\Status;
use App\Enums\UserType;
use App\User;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CreatSuperadminOnUserTable extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        User::create([
            'name' => 'bonfire-administrator',
            'username' => 'superadmin',
            'password' => bcrypt('superadmin031819'),
            'status' => Status::ACTIVE,
            'type' => UserType::SUPERADMIN
        ]);
    }

    /**
     * Reverts the database seeder.
     */
    public function down(): void
    {
        $superadmin = User::where(['username' ,'superadmin'])
            ->first();

        if ($superadmin) {
            $superadmin->forceDelete();
        }
    }
}
