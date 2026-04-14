<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPrepTimeColumnInProductUomAndKitchenItemSetupTable extends Migration
{
    private $table = [
        [
            'cdis_product_uom_packaging',
            'allergens'
        ],
        [
            'cdis_kitchen_item_setup_detail',
            'product_uom_packaging_bid'
        ]
    ];
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $productUomTable = $this->table[0][0];
        foreach ($this->table as $alterTable) {
            
            if (Schema::hasTable($alterTable[0])) {
                Schema::table($alterTable[0], function (Blueprint $table) use ($alterTable, $productUomTable) {

                    if ($alterTable[0] == $productUomTable) {
                        if (! Schema::hasColumn($alterTable[0], 'calories')) {
                            $table->decimal('calories', 23, 6)->nullabe()->after('pack_content');
                        }
                        if (! Schema::hasColumn($alterTable[0], 'menu_description')) {
                            $table->string('menu_description', 23, 6)->nullabe()->after('calories');
                        }
                        if (! Schema::hasColumn($alterTable[0], 'allergens')) {
                            $table->string('allergens', 23, 6)->nullabe()->after('menu_description');
                        }
                        
                    }

                    if (! Schema::hasColumn($alterTable[0], 'max_prep_time')) {
                        $table->decimal('max_prep_time', 23, 6)->nullabe()->after($alterTable[1]);
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
        $productUomTable = $this->table[0][0];
        foreach ($this->table as $alterTable) {
            if (Schema::hasTable($alterTable[0])) {
                Schema::table($alterTable[0], function (Blueprint $table)  use ($alterTable, $productUomTable) {
                    
                    if ($alterTable[0] == $productUomTable) {
                        if (Schema::hasColumn($alterTable[0], 'calories')) {
                            $table->dropColumn('calories');
                        }
                        if (Schema::hasColumn($alterTable[0], 'menu_description')) {
                            $table->dropColumn('menu_description');
                        }
                        if (Schema::hasColumn($alterTable[0], 'allergens')) {
                            $table->dropColumn('allergens');
                        }
                    }

                    if (Schema::hasColumn($alterTable[0], 'max_prep_time')) {
                        $table->dropColumn('max_prep_time');
                    }
                });
            }
        }
        
    }
}
