<?php

use App\Entities\Configuration;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CreateInitialConfiguration extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        Configuration::insert([
            ['attribute' => 'syncing_order', 'value' => "[{name: 'Transaction'},{name: 'Zread'},{name: 'Cashier'},{name: 'Journal'},{name: 'Cash Drawer'},{name: 'Audit Trail'}]" ],
            ['attribute' => 'pos_to_cdis_entry_limit', 'value' => '60'],
            ['attribute' => 'cdis_to_pos_entry_limit', 'value' => '60'],
        ]);

    }

    /**
     * Reverts the database seeder.
     */
    public function down(): void
    {
        // Remove your data
    }
}
