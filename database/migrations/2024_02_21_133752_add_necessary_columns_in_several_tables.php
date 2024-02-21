<?php

use App\Enums\TradeType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNecessaryColumnsInSeveralTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('cdis_payment_term_settings')) {
            Schema::table('cdis_payment_term_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('cdis_payment_term_settings', 'days_till_due')) {
                    $table->decimal('days_till_due', 23, 6)->default(0.000000)->after('name');
                }
            });
        }

        if (Schema::hasTable('cdis_vendor')) {
            Schema::table('cdis_vendor', function (Blueprint $table) {
                if (! Schema::hasColumn('cdis_vendor', 'trade_type')) {
                    $table->tinyInteger('trade_type')->default(TradeType::TRADE_OR_NON_TRADE)->after('payment_term_settings_bid');
                }
                if (! Schema::hasColumn('cdis_vendor', 'business_style')) {
                    $table->text('business_style', 30)->after('description');
                }
                if (! Schema::hasColumn('cdis_vendor', 'branch_as_vendor_bid')) {
                    $table->unsignedBigInteger('branch_as_vendor_bid')->nullable()->after('contact_number');
                }
            });
        }

        if (Schema::hasTable('cdis_product_uom_packaging')) {
            Schema::table('cdis_product_uom_packaging', function (Blueprint $table) {
                if (! Schema::hasColumn('cdis_product_uom_packaging', 'trade_type')) {
                    $table->tinyInteger('trade_type')->default(TradeType::TRADE_OR_NON_TRADE)->after('variant_option');
                }
                if (! Schema::hasColumn('cdis_product_uom_packaging', 'is_reduce_composition')) {
                    $table->tinyInteger('is_reduce_composition')->default('1')->after('is_finished_good');
                }
                if (! Schema::hasColumn('cdis_product_uom_packaging', 'trade_type')) {
                    $table->tinyInteger('has_expiry')->after('is_default')->default(0);
                }
                if (! Schema::hasColumn('cdis_product_uom_packaging', 'sort_index')) {
                    $table->integer('sort_index')->after('has_expiry')->nullable()->default(0);
                }
            });
        }

        if (Schema::hasTable('cdis_product_structure_detail')) {
            Schema::table('cdis_product_structure_detail', function (Blueprint $table) {
                if (! Schema::hasColumn('cdis_product_structure_detail', 'is_reduce_composition')) {
                    $table->tinyInteger('is_reduce_composition')->default('1')->after('quantity');
                }
                if (! Schema::hasColumn('cdis_product_structure_detail', 'classification')) {
                    $table->tinyInteger('classification')->nullable()->after('quantity');
                }
            });
        }

        if (Schema::hasTable('cdis_terminal_transaction')) {
            Schema::table('cdis_terminal_transaction', function (Blueprint $table) {
                if (Schema::hasColumn('cdis_terminal_transaction', 'transaction_id')) {
                    $table->string('transaction_id', 64)->default('0')->change();
                }
            });
        }

        if (Schema::hasTable('cdis_cost_and_price_change_detail')) {
            Schema::table('cdis_cost_and_price_change_detail', function (Blueprint $table) {
                if (! Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_discount_type')) {
                    $table->tinyInteger('old_discount_type')->nullable()->after('new_value');
                }
                if (! Schema::hasColumn('cdis_cost_and_price_change_detail', 'discount_type')) {
                    $table->tinyInteger('discount_type')->nullable()->after('old_discount_type');
                }

                if (! Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_discount_1')) {
                    $table->decimal('old_discount_1', 23, 6)->nullable()->after('discount_type');
                }
                if (! Schema::hasColumn('cdis_cost_and_price_change_detail', 'discount_1')) {
                    $table->decimal('discount_1', 23, 6)->default(0.000000)->after('old_discount_1');
                }

                if (! Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_discount_2')) {
                    $table->decimal('old_discount_2', 23, 6)->nullable()->after('discount_1');
                }
                if (! Schema::hasColumn('cdis_cost_and_price_change_detail', 'discount_2')) {
                    $table->decimal('discount_2', 23, 6)->default(0.000000)->after('old_discount_2');
                }

                if (! Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_discount_3')) {
                    $table->decimal('old_discount_3', 23, 6)->nullable()->after('discount_2');
                }
                if (! Schema::hasColumn('cdis_cost_and_price_change_detail', 'discount_3')) {
                    $table->decimal('discount_3', 23, 6)->default(0.000000)->after('old_discount_3');
                }

                if (! Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_discount_4')) {
                    $table->decimal('old_discount_4', 23, 6)->nullable()->after('discount_3');
                }
                if (! Schema::hasColumn('cdis_cost_and_price_change_detail', 'discount_4')) {
                    $table->decimal('discount_4', 23, 6)->default(0.000000)->after('old_discount_4');
                }
                if (! Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_adjustment')) {
                    $table->decimal('old_adjustment', 23, 6)->nullable()->after('discount_4');
                }
                if (! Schema::hasColumn('cdis_cost_and_price_change_detail', 'adjustment')) {
                    $table->decimal('adjustment', 23, 6)->default(0.000000)->after('old_adjustment');
                }
                if (! Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_net_amount')) {
                    $table->decimal('old_net_amount', 23, 6)->nullable()->after('adjustment');
                }
                if (! Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_percentage_change')) {
                    $table->decimal('old_percentage_change', 23, 6)->nullable()->after('old_net_amount');
                }
            });
        }

        if (Schema::hasTable('cdis_cost_and_price_change')) {
            Schema::table('cdis_cost_and_price_change', function (Blueprint $table) {
                if (! Schema::hasColumn('cdis_cost_and_price_change', 'is_immediate')) {
                    $table->boolean('is_immediate')->default(0)->after('expires_at');
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
        if (Schema::hasTable('cdis_payment_term_settings')) {
            Schema::table('cdis_payment_term_settings', function (Blueprint $table) {
                if (Schema::hasColumn('cdis_payment_term_settings', 'days_till_due')) {
                    $table->dropColumn(['days_till_due']);
                }
            });
        }

        if (Schema::hasTable('cdis_vendor')) {
            Schema::table('cdis_vendor', function (Blueprint $table) {
                if (Schema::hasColumn('cdis_vendor', 'trade_type')) {
                    $table->dropColumn('trade_type');
                }
                if (Schema::hasColumn('cdis_vendor', 'business_style')) {
                    $table->dropColumn('business_style');
                }
                if (Schema::hasColumn('cdis_vendor', 'branch_as_vendor_bid')) {
                    $table->dropColumn('branch_as_vendor_bid');
                }
            });
        }

        if (Schema::hasTable('cdis_product_uom_packaging')) {
            Schema::table('cdis_product_uom_packaging', function (Blueprint $table) {
                if (Schema::hasColumn('cdis_product_uom_packaging', 'trade_type')) {
                    $table->dropColumn('trade_type');
                }
                if (Schema::hasColumn('cdis_product_uom_packaging', 'is_reduce_composition')) {
                    $table->dropColumn('is_reduce_composition');
                }
                if (Schema::hasColumn('cdis_product_uom_packaging', 'has_expiry')) {
                    $table->dropColumn('has_expiry');
                }
                if (Schema::hasColumn('cdis_product_uom_packaging', 'sort_index')) {
                    $table->dropColumn('sort_index');
                }
            });
        }

        if (Schema::hasTable('cdis_product_structure_detail')) {
            Schema::table('cdis_product_structure_detail', function (Blueprint $table) {
                if (Schema::hasColumn('cdis_product_structure_detail', 'is_reduce_composition')) {
                    $table->dropColumn('is_reduce_composition');
                }
                if (Schema::hasColumn('cdis_product_structure_detail', 'classification')) {
                    $table->dropColumn('classification');
                }
            });
        }

        if (Schema::hasTable('cdis_terminal_transaction')) {
            Schema::table('cdis_terminal_transaction', function (Blueprint $table) {
                if (Schema::hasColumn('cdis_terminal_transaction', 'transaction_id')) {
                    // Leave it as it is, no need to return previous data type
                    // because it will result an errors due to changes of data value
                    // $table->bigInteger('transaction_id')->default(0)->change();
                }
            });
        }

        if (Schema::hasTable('cdis_cost_and_price_change_detail')) {
            Schema::table('cdis_cost_and_price_change_detail', function (Blueprint $table) {
                if (Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_discount_type')) {
                    $table->dropColumn('old_discount_type');
                }
                if (Schema::hasColumn('cdis_cost_and_price_change_detail', 'discount_type')) {
                    $table->dropColumn('discount_type');
                }
                if (Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_discount_1')) {
                    $table->dropColumn('old_discount_1');
                }
                if (Schema::hasColumn('cdis_cost_and_price_change_detail', 'discount_1')) {
                    $table->dropColumn('discount_1');
                }
                if (Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_discount_2')) {
                    $table->dropColumn('old_discount_2');
                }
                if (Schema::hasColumn('cdis_cost_and_price_change_detail', 'discount_2')) {
                    $table->dropColumn('discount_2');
                }
                if (Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_discount_3')) {
                    $table->dropColumn('old_discount_3');
                }
                if (Schema::hasColumn('cdis_cost_and_price_change_detail', 'discount_3')) {
                    $table->dropColumn('discount_3');
                }
                if (Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_discount_4')) {
                    $table->dropColumn('old_discount_4');
                }
                if (Schema::hasColumn('cdis_cost_and_price_change_detail', 'discount_4')) {
                    $table->dropColumn('discount_4');
                }
                if (Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_adjustment')) {
                    $table->dropColumn('old_adjustment');
                }
                if (Schema::hasColumn('cdis_cost_and_price_change_detail', 'adjustment')) {
                    $table->dropColumn('adjustment');
                }
                if (Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_net_amount')) {
                    $table->dropColumn('old_net_amount');
                }
                if (Schema::hasColumn('cdis_cost_and_price_change_detail', 'old_percentage_change')) {
                    $table->dropColumn('old_percentage_change');
                }
            });
        }

        if (Schema::hasTable('cdis_cost_and_price_change')) {
            Schema::table('cdis_cost_and_price_change', function (Blueprint $table) {
                if (Schema::hasColumn('cdis_cost_and_price_change', 'is_immediate')) {
                    $table->dropColumn('is_immediate');
                }
            });
        }
    }
}
