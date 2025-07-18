<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Enums\API\DeviceType;
use App\Enums\BillingType;
use App\Enums\PaymentStatus;
use App\Enums\KDS\OrderType;

class AddSomeColumnInPosTerminalTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('pos_terminal_transactions')) {
            Schema::table('pos_terminal_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('pos_terminal_transactions', 'device_type')) {
                    $table->tinyInteger('device_type')->default(DeviceType::SIRIUS_POS)->after('type');
                }
                if (! Schema::hasColumn('pos_terminal_transactions', 'payment_status')) {
                    $table->tinyInteger('payment_status')->default(PaymentStatus::CREATED)->after('payment');
                }
                if (! Schema::hasColumn('pos_terminal_transactions', 'order_type')) {
                    $table->tinyInteger('order_type')->default(OrderType::DINE_IN)->after('order_status');
                }
                if (! Schema::hasColumn('pos_terminal_transactions', 'order_schedule')) {
                    $table->timestamp('order_schedule')->nullable()->after('order_type');
                }
                if (! Schema::hasColumn('pos_terminal_transactions', 'billing_type')) {
                    $table->tinyInteger('billing_type')->default(BillingType::COD)->after('order_schedule');
                }
                if (! Schema::hasColumn('pos_terminal_transactions', 'total_delivery_fee')) {
                    $table->tinyInteger('total_delivery_fee')->default(0.000000)->after('total_discount_amount');
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
        Schema::table('pos_terminal_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('pos_terminal_transactions', 'device_type')) {
                $table->dropColumn('device_type');
            }
            if (Schema::hasColumn('pos_terminal_transactions', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('pos_terminal_transactions', 'order_type')) {
                $table->dropColumn('order_type');
            }
            if (Schema::hasColumn('pos_terminal_transactions', 'billing_type')) {
                $table->dropColumn('billing_type');
            }
            if (Schema::hasColumn('pos_terminal_transactions', 'order_schedule')) {
                $table->dropColumn('order_schedule');
            }
             if (Schema::hasColumn('pos_terminal_transactions', 'total_delivery_fee')) {
                $table->dropColumn('total_delivery_fee');
            }
        });
    }
}
