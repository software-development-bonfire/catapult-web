<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTableLocationAndTableColumns extends Migration
{
    public function up()
    {
        Schema::table('table_location', function (Blueprint $table) {
            $table->string('location_name')->nullable()->after('name');
            $table->integer('no_of_tables')->default(0)->after('location_name');
            $table->integer('no_of_seats')->default(0)->after('no_of_tables');
        });

        Schema::table('table', function (Blueprint $table) {
            $table->string('transaction_no')->nullable()->after('location_id');
            $table->string('table_ref')->nullable()->after('transaction_no');
            $table->integer('seat_number')->default(0)->after('table_ref');
            $table->boolean('is_available')->default(true)->after('availability');
            $table->date('date')->nullable()->after('status');
            $table->decimal('total', 23, 6)->default(0)->after('date');
            $table->integer('number_of_guest')->default(0)->after('total');
            $table->string('shape')->nullable()->after('number_of_guest');
            $table->decimal('positionX', 10, 2)->default(0)->after('shape');
            $table->decimal('position_y', 10, 2)->default(0)->after('positionX');
            $table->decimal('height', 10, 2)->default(0)->after('position_y');
            $table->decimal('width', 10, 2)->default(0)->after('height');
            $table->decimal('angle', 10, 2)->default(0)->after('width');
            $table->boolean('is_placed')->default(false)->after('angle');
            $table->integer('no_of_items')->default(0)->after('is_placed');
        });
    }

    public function down()
    {
        Schema::table('table_location', function (Blueprint $table) {
            $table->dropColumn(['location_name', 'no_of_tables', 'no_of_seats']);
        });

        Schema::table('table', function (Blueprint $table) {
            $table->dropColumn([
                'transaction_no', 'table_ref', 'seat_number',
                'is_available', 'date', 'total', 'number_of_guest',
                'shape', 'positionX', 'position_y', 'height', 'width',
                'angle', 'is_placed', 'no_of_items',
            ]);
        });
    }
}
