<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusColumnInSeveralTables extends Migration
{
    private $tables = [
        [
            'table' =>  'cdis_brand',
            'after_column' => 'name'
        ],
        [
            'table' =>  'cdis_unit_of_measurement',
            'after_column' => 'name'
        ],
        [
            'table' =>  'cdis_product_variant',
            'after_column' => 'description'
        ],
        [
            'table' =>  'cdis_tags',
            'after_column' => 'name'
        ],
        [
            'table' =>  'cdis_discount_settings',
            'after_column' => 'receipt_count'
        ],
        [
            'table' =>  'cdis_charges_settings',
            'after_column' => 'is_auto_apply'
        ],
        [
            'table' =>  'cdis_payment_term_settings',
            'after_column' => 'name'
        ],
    ];
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->tables as $key => $schemaTable) {
            $tableName = $schemaTable['table'];
            $afterColumn = $schemaTable['after_column'];
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName, $afterColumn) {
                    if (! Schema::hasColumn($tableName, 'status')) {
                        $table->tinyInteger('status')->after($afterColumn)->default(\App\Enums\Status::ACTIVE);
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->tables as $key => $schemaTable) {
            $tableName = $schemaTable['table'];
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'status')) {
                        $table->dropColumn('status');
                    }
                });
            }
        }
    }
}
