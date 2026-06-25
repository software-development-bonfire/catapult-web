<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTimestampsAndUniqueToKitchenDisplayDetail extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('kitchen_display_detail')) {
            return;
        }

        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('kitchen_display_detail', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('terminal_number');
            }

            if (!Schema::hasColumn('kitchen_display_detail', 'end_at')) {
                $table->timestamp('end_at')->nullable()->after('started_at');
            }
        });

        /*
        if (!$this->indexExists('kitchen_display_detail', 'uq_kdd_business_key')) {
            Schema::table('kitchen_display_detail', function (Blueprint $table) {
                $table->unique(
                    [
                        'head_bid',
                        'transaction_product_bid',
                        'product_uom_packaging_bid',
                        'kitchen_station_bid',
                        'terminal_number',
                    ],
                    'uq_kdd_business_key'
                );
            });
        }*/
    }

    public function down()
    {
        if (!Schema::hasTable('kitchen_display_detail')) {
            return;
        }

        if ($this->indexExists('kitchen_display_detail', 'uq_kdd_business_key')) {
            Schema::table('kitchen_display_detail', function (Blueprint $table) {
                $table->dropUnique('uq_kdd_business_key');
            });
        }

        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            if (Schema::hasColumn('kitchen_display_detail', 'end_at')) {
                $table->dropColumn('end_at');
            }

            if (Schema::hasColumn('kitchen_display_detail', 'started_at')) {
                $table->dropColumn('started_at');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
}