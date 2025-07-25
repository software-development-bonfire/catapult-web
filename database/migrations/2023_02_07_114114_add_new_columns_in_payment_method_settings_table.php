<?php

use App\Traits\MigrationTrait;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewColumnsInPaymentMethodSettingsTable extends Migration
{
    use MigrationTrait;

    private $table = 'cdis_payment_method_settings';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
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

                $foreignKeyName = $this->customizedIndexUniqueForeignKeyName($this->table, 'payment_charge_type_bid', 'foreign');
                $table->foreign('payment_charge_type_bid', $foreignKeyName)
                    ->references('bid')
                    ->on('cdis_payment_charge_type')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');

                $foreignKeyName = $this->customizedIndexUniqueForeignKeyName($this->table, 'payment_tender_type_bid', 'foreign');
                $table->foreign('payment_tender_type_bid', $foreignKeyName)
                    ->references('bid')
                    ->on('cdis_payment_tender_type')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');

                $foreignKeyName = $this->customizedIndexUniqueForeignKeyName($this->table, 'payment_transaction_type_bid', 'foreign');
                $table->foreign('payment_transaction_type_bid', $foreignKeyName)
                    ->references('bid')
                    ->on('cdis_payment_transaction_type')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });
        }
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable($this->table)) {
            Schema::table($this->table, function (Blueprint $table) {
                // It happens due to some data, so we
                // Drop foreign keys first before dropping the column
                $fkPaymentChargeType = $this->customizedIndexUniqueForeignKeyName($this->table, 'payment_charge_type_bid', 'foreign');
                $fkPaymentTenderType = $this->customizedIndexUniqueForeignKeyName($this->table, 'payment_tender_type_bid', 'foreign');
                $fkTransactionType = $this->customizedIndexUniqueForeignKeyName($this->table, 'payment_transaction_type_bid', 'foreign');

                if (Schema::hasColumn($this->table, 'payment_charge_type_bid')) {
                    $table->dropForeign($fkPaymentChargeType);
                    $table->dropColumn('payment_charge_type_bid');
                }

                if (Schema::hasColumn($this->table, 'payment_tender_type_bid')) {
                    $table->dropForeign($fkPaymentTenderType);
                    $table->dropColumn('payment_tender_type_bid');
                }

                if (Schema::hasColumn($this->table, 'payment_transaction_type_bid')) {
                    $table->dropForeign($fkTransactionType);
                    $table->dropColumn('payment_transaction_type_bid');
                }

                // Drop non-foreign key columns
                if (Schema::hasColumn($this->table, 'is_default')) {
                    $table->dropColumn('is_default');
                }
                if (Schema::hasColumn($this->table, 'get_exact_amount')) {
                    $table->dropColumn('get_exact_amount');
                }
                if (Schema::hasColumn($this->table, 'status')) {
                    $table->dropColumn('status');
                }
            });
        }

        Schema::enableForeignKeyConstraints();
    }
}
