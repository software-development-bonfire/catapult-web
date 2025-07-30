<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsCdisProductUomPackagingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        if (Schema::hasTable('cdis_product_uom_packaging')) {
            Schema::table('cdis_product_uom_packaging', function (Blueprint $table) {
                if (! Schema::hasColumn('cdis_product_uom_packaging', 'is_weighted')) {
                    $table->tinyInteger('is_weighted')->default(0)->after('is_addon');
                }
                if (! Schema::hasColumn('cdis_product_uom_packaging', 'is_price_point')) {
                    $table->tinyInteger('is_price_point')->default(0)->after('is_weighted');
                }
                if (! Schema::hasColumn('cdis_product_uom_packaging', 'is_tag_reference')) {
                    $table->tinyInteger('is_tag_reference')->default(0)->after('is_price_point');
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
        if (Schema::hasTable('cdis_product_uom_packaging')) {
            Schema::table('cdis_product_uom_packaging', function (Blueprint $table) {
                if (Schema::hasColumn('cdis_product_uom_packaging', 'is_weighted')) {
                    $table->dropColumn('is_weighted');
                }
                if (Schema::hasColumn('cdis_product_uom_packaging', 'is_price_point')) {
                    $table->dropColumn('is_price_point');
                }
                if (Schema::hasColumn('cdis_product_uom_packaging', 'is_tag_reference')) {
                    $table->dropColumn('is_tag_reference');
                }
            });
        }
    }
}
