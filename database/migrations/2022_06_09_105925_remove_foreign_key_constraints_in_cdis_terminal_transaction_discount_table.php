<?php

use App\Traits\MigrationTrait;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveForeignKeyConstraintsInCdisTerminalTransactionDiscountTable extends Migration
{
    use MigrationTrait;
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('cdis_terminal_transaction_discount', function (Blueprint $table) {
            if ($this->hasForeignKey('cdis_terminal_transaction_discount', 'discount_transaction_product_bid_foreign')) {
                $table->dropForeign('discount_transaction_product_bid_foreign');
            }
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_terminal_transaction_discount', function (Blueprint $table) {
            if (! $this->hasForeignKey('cdis_terminal_transaction_discount', 'discount_transaction_product_bid_foreign')) {
                $table->foreign('transaction_product_bid', 'discount_transaction_product_bid_foreign')
                    ->references('bid')
                    ->on('cdis_terminal_transaction_product')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            }
        });
    }
}
