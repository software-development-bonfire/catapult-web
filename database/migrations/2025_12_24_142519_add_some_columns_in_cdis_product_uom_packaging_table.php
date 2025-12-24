<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnsInCdisProductUomPackagingTable extends Migration
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
                if (!Schema::hasColumn('cdis_product_uom_packaging', 'menu_description')) {
                    $table->text('menu_description')->nullable()->after('pack_content');
                }

                if (!Schema::hasColumn('cdis_product_uom_packaging', 'allergens')) {
                    $table->string('allergens')->nullable()->after('menu_description');
                }

                if (!Schema::hasColumn('cdis_product_uom_packaging', 'calories')) {
                    $table->string('calories')->nullable()->after('menu_description');
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
                if (Schema::hasColumn('cdis_product_uom_packaging', 'menu_description')) {
                        $table->dropColumn('menu_description');
                    }

                    if (Schema::hasColumn('cdis_product_uom_packaging', 'allergens')) {
                        $table->dropColumn('allergens');
                    }

                    if (Schema::hasColumn('cdis_product_uom_packaging', 'calories')) {
                        $table->dropColumn('calories');
                    }
            });
        }
    }
}
