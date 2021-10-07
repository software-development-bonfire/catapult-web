<?php

use App\Enums\MappingType;
use App\Entities\SyncEntry;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class AddDefaultSyncEntries extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $entries = [
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'branch', 'alias' => 'BR' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'brand', 'alias' => 'BD' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'inventory_location', 'alias' => 'IL' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'inventory_location_tag', 'alias' => 'ILT' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'kitchen_device_printer', 'alias' => 'KD' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'kitchen_device_printer_branch', 'alias' => 'KDB' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'kitchen_item_setup', 'alias' => 'KIH' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'kitchen_item_setup_detail', 'alias' => 'KID' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'kitchen_station', 'alias' => 'KS' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'kitchen_station_process', 'alias' => 'KSP' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'kitchen_user', 'alias' => 'KUH' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'kitchen_user_branch', 'alias' => 'KUB' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'kitchen_user_station', 'alias' => 'KUS' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'packaging_vendor', 'alias' => 'PV' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'packaging_vendor_branch_cost', 'alias' => 'PVC' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'product', 'alias' => 'PR' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'product_branch_availability', 'alias' => 'BA' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'product_branch_price', 'alias' => 'BP' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'product_category', 'alias' => 'CT' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'product_uom_packaging', 'alias' => 'PUP' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'product_structure', 'alias' => 'PSH' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'product_structure_detail', 'alias' => 'PSD' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'product_pricing_type', 'alias' => 'PP' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'product_variant', 'alias' => 'VRH' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'product_variant_option', 'alias' => 'VRO' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'unit_of_measurement', 'alias' => 'UOM' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'vendor', 'alias' => 'VND' ],
            [ 'type' => MappingType::CDIS_TO_POS, 'name' => 'vendor_branch', 'alias' => 'VNB' ],

            [ 'type' => MappingType::POS_TO_CDIS, 'name' => 'transaction', 'alias' => 'TR' ],
            [ 'type' => MappingType::POS_TO_CDIS, 'name' => 'zread', 'alias' => 'ZR' ],
            [ 'type' => MappingType::POS_TO_CDIS, 'name' => 'audit_trail', 'alias' => 'AT' ],
            [ 'type' => MappingType::POS_TO_CDIS, 'name' => 'cash_breakdown', 'alias' => 'CB' ],
            [ 'type' => MappingType::POS_TO_CDIS, 'name' => 'cash_drawer', 'alias' => 'CD' ],
        ];

        foreach ($entries as $entry) {
            SyncEntry::create($entry);
        }

    }

    /**
     * Reverts the database seeder.
     */
    public function down(): void
    {
        SyncEntry::truncate();
    }
}
