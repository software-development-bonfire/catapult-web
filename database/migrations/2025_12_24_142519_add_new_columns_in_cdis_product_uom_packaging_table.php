<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewColumnsInCdisProductUomPackagingTable extends Migration
{
      protected $tableName = 'cdis_product_uom_packaging';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (!Schema::hasColumn($this->tableName, 'menu_description')) {
                    $table->text('menu_description')->nullable()->after('pack_content');
                }

                if (!Schema::hasColumn($this->tableName, 'allergens')) {
                    $table->string('allergens')->nullable()->after('menu_description');
                }

                if (!Schema::hasColumn($this->tableName, 'calories')) {
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
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (Schema::hasColumn($this->tableName, 'menu_description')) {
                    $table->dropColumn('menu_description');
                }

                if (Schema::hasColumn($this->tableName, 'allergens')) {
                    $table->dropColumn('allergens');
                }

                if (Schema::hasColumn($this->tableName, 'calories')) {
                    $table->dropColumn('calories');
                }
            });
        }
    }
}
