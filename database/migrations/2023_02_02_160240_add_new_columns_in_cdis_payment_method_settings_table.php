<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewColumnsInCdisPaymentMethodSettingsTable extends Migration
{ 
    private $table = 'cdis_payment_method_settings';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->table)) {
            Schema::table($this->table, function (Blueprint $table) {
                if (! Schema::hasColumn($this->table, 'is_default')) {
                    $table->tinyInteger('is_default')->after('receipt_count')->default(\App\Enums\Status::INACTIVE);
                }
                if (! Schema::hasColumn($this->table, 'get_exact_amount')) {
                    $table->tinyInteger('get_exact_amount')->after('is_default')->default(\App\Enums\Status::INACTIVE);
                }
                if (! Schema::hasColumn($this->table, 'payment_charge_type_bid')) {
                    $table->unsignedBigInteger('payment_charge_type_bid')->after('open_cash_drawer')->nullable();
                }
                if (! Schema::hasColumn($this->table, 'payment_tender_type_bid')) {
                    $table->unsignedBigInteger('payment_tender_type_bid')->after('payment_charge_type_bid')->nullable();
                }
                if (! Schema::hasColumn($this->table, 'payment_transaction_type_bid')) {
                    $table->unsignedBigInteger('payment_transaction_type_bid')->after('payment_tender_type_bid')->nullable();
                }
                if (! Schema::hasColumn($this->table, 'status')) {
                    $table->tinyInteger('status')->after('payment_transaction_type_bid')->default(\App\Enums\Status::ACTIVE);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable($this->table)) {
            Schema::table($this->table, function (Blueprint $table) {
                if (Schema::hasColumn($this->table, 'is_default')) {
                    $table->dropColumn('is_default');
                }
                if (Schema::hasColumn($this->table, 'get_exact_amount')) {
                    $table->dropColumn('get_exact_amount');
                }
                if (Schema::hasColumn($this->table, 'payment_charge_type_bid')) {
                    $table->dropColumn('payment_charge_type_bid');
                }
                if (Schema::hasColumn($this->table, 'payment_tender_type_bid')) {
                    $table->dropColumn('payment_tender_type_bid');
                }
                if (Schema::hasColumn($this->table, 'payment_transaction_type_bid')) {
                    $table->dropColumn('payment_transaction_type_bid');
                }
                if (Schema::hasColumn($this->table, 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
    }
}
