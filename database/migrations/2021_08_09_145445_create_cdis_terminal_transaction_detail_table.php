<?php

use App\Enums\CDIS\CustomerType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisTerminalTransactionDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_terminal_transaction_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('transaction_head_bid');
            $table->bigInteger('or_number');
            $table->integer('split_number');
            $table->decimal('total', 23, 6)->default(0.000000);
            $table->decimal('discount_amount', 23, 6)->default(0.000000);
            $table->decimal('free_items_amount', 23, 6)->default(0.000000);
            $table->decimal('quantity', 23, 6)->default(0.000000);
            $table->decimal('original_amount', 23, 6)->default(0.000000);
            $table->decimal('vat_deduct_amount', 23, 6)->default(0.000000);
            $table->decimal('vat_exempt_amount', 23, 6)->default(0.000000);
            $table->decimal('local_tax_amount', 23, 6)->default(0.000000);
            $table->decimal('tax_amount', 23, 6)->default(0.000000);
            $table->decimal('service_charge', 23, 6)->default(0.000000);
            $table->decimal('vatable_sales', 23, 6)->default(0.000000);
            $table->decimal('zero_rated_sales', 23, 6)->default(0.000000);
            $table->decimal('eligible_amount_to_earn_points', 23, 6)->default(0.000000);
            $table->decimal('total_tender', 23, 6)->default(0.000000);
            $table->tinyInteger('customer_type')->default(CustomerType::WALK_IN);
            $table->unsignedBigInteger('customer_bid')->nullable();
            $table->string('customer_name', 64)->nullable();
            $table->string('customer_address', 512)->nullable();
            $table->unsignedBigInteger('cashier_bid')->nullable();
            $table->string('cashier_name', 64)->nullable();
            $table->string('remarks', 512)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('transaction_head_bid', 'detail_transaction_bid_foreign')
                ->references('bid')
                ->on('cdis_terminal_transaction')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cdis_terminal_transaction_detail');
    }
}
