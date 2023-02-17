<?php

use App\Enums\CDIS\TransactionType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisTerminalTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_terminal_transaction', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('terminal_bid');
            $table->bigInteger('transaction_id')->default(0);
            $table->dateTime('date')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->decimal('amount', 23, 6)->default(0.000000);
            $table->tinyInteger('transaction_type')->default(0);
            $table->tinyInteger('is_zread')->default(0);
            $table->tinyInteger('type')->default(TransactionType::POS);
            $table->tinyInteger('status')->default(1);
            $table->decimal('gross', 23, 6)->default(0.000000);
            $table->decimal('total_quantity', 23, 6)->default(0.000000);
            $table->decimal('total_free_items_amount', 23, 6)->default(0.000000);
            $table->decimal('total_local_tax_amount', 23, 6)->default(0.000000);
            $table->decimal('total_tax_amount', 23, 6)->default(0.000000);
            $table->decimal('total_discount_amount', 23, 6)->default(0.000000);
            $table->decimal('total_vat_deduct_amount', 23, 6)->default(0.000000);
            $table->decimal('total_vat_exempt_amount', 23, 6)->default(0.000000);
            $table->decimal('total_vatable_sales', 23, 6)->default(0.000000);
            $table->decimal('total_zero_rated_sales', 23, 6)->default(0.000000);
            $table->dateTime('log_date')->nullable();
            $table->bigInteger('order_number')->nullable();
            $table->string('table_number', 64)->nullable();
            $table->integer('guest_count')->default(0);
            $table->string('remarks', 512)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cdis_terminal_transaction');
    }
}
