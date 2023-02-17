<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNecessaryColumnsInCdisTerminalTransactionAddonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_terminal_transaction_addon', function (Blueprint $table) {
            $table->string('description', 512)->nullable()->after('name');
            $table->string('long_description', 1024)->nullable()->after('description');
            $table->string('menu_code', 128)->nullable()->after('long_description');
            $table->unsignedBigInteger('category_bid')->nullable()->after('menu_code');
            $table->string('category_name', 128)->nullable()->after('category_bid');
            $table->unsignedBigInteger('order_type_id')->nullable()->after('tax_percentage');
            $table->string('order_type_name', 64)->nullable()->after('order_type_id');
            $table->tinyInteger('is_free')->default(0)->after('order_type_name');
            $table->tinyInteger('is_vatable')->default(1)->after('is_free');
            $table->decimal('amount_discount', 23, 6)->default(0.000000)->after('zero_rated_sales');
            $table->decimal('vat_deduct', 23, 6)->default(0.000000)->after('tax');
            $table->tinyInteger('split_number')->nullable()->after('vat_exempt');
            $table->unsignedBigInteger('supervisor_bid')->nullable()->after('remarks');
            $table->string('supervisor_name', 64)->nullable()->after('supervisor_bid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_terminal_transaction_addon', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('long_description');
            $table->dropColumn('menu_code');
            $table->dropColumn('category_bid');
            $table->dropColumn('category_name');
            $table->dropColumn('order_type_id');
            $table->dropColumn('order_type_name');
            $table->dropColumn('is_free');
            $table->dropColumn('is_vatable');
            $table->dropColumn('amount_discount');
            $table->dropColumn('vat_deduct');
            $table->dropColumn('split_number');
            $table->dropColumn('supervisor_bid');
            $table->dropColumn('supervisor_name');
        });
    }
}
