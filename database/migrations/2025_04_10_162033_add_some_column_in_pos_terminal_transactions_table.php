<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Enums\API\DeviceType;
use App\Enums\PaymentStatus;

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
                    $table->tinyInteger('device_type')->default(DeviceType::POS)->after('type');
                }
                if (! Schema::hasColumn('pos_terminal_transactions', 'payment_status')) {
                    $table->tinyInteger('payment_status')->default(PaymentStatus::CREATED)->after('payment');
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
        });
    }
}
