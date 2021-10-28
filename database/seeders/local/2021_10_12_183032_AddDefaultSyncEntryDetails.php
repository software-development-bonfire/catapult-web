<?php

use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;
use App\Entities\SyncEntry;
use App\Entities\SyncEntryDetail;

class AddDefaultSyncEntryDetails extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $transactionSyncEntry = SyncEntry::where(['name' => 'transaction', 'alias' => 'TR'])->first();

        $transactionSyncEntryDetails = [
            ['name' => 'terminal_transaction', 'alias' => 'TH'],
            ['name' => 'terminal_transaction_detail', 'alias' => 'TD'],
            ['name' => 'terminal_transaction_detail_discount', 'alias' => 'TTD'],
            ['name' => 'terminal_transaction_discount', 'alias' => 'PD'],
            ['name' => 'terminal_transaction_payment_method', 'alias' => 'PM'],
            ['name' => 'terminal_transaction_product', 'alias' => 'PR'],
        ];

        foreach ($transactionSyncEntryDetails as $transactionSyncEntryDetail) {
            $transactionSyncEntry->detail()->create($transactionSyncEntryDetail);
        }

        $zreadSyncEntry = SyncEntry::where(['name' => 'zread', 'alias' => 'ZR'])->first();

        $zreadSyncEntryDetails = [
            ['name' => 'zread', 'alias' => 'ZH'],
            ['name' => 'zread_cash_breakdown_detail', 'alias' => 'ZCB'],
            ['name' => 'zread_cashier_sales_summary', 'alias' => 'ZCS'],
            ['name' => 'zread_regular_discount', 'alias' => 'ZRD'],
            ['name' => 'zread_tender_detail', 'alias' => 'ZTD'],
        ];

        foreach ($zreadSyncEntryDetails as $zreadSyncEntryDetail) {
            $zreadSyncEntry->detail()->create($zreadSyncEntryDetail);
        }

        $cashBreakdownSyncEntry = SyncEntry::where(['name' => 'cash_breakdown', 'alias' => 'CB'])->first();

        $cashBreakdownSyncEntryDetails = [
            ['name' => 'cash_breakdown', 'alias' => 'CH'],
            ['name' => 'cash_breakdown_detail', 'alias' => 'CD'],
        ];

        foreach ($cashBreakdownSyncEntryDetails as $cashBreakdownSyncEntryDetail) {
            $cashBreakdownSyncEntry->detail()->create($cashBreakdownSyncEntryDetail);
        }
    }

    /**
     * Reverts the database seeder.
     */
    public function down(): void
    {
        SyncEntryDetail::all()->delete();
    }
}
