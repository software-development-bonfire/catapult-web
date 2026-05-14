<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'prefix' => 'kds/v1',
    'namespace' => 'KDS\v1'
], function () {
    // ============================================
    // AUTH (Shared)
    // ============================================
    Route::post('login', 'LoginController@login');
    Route::post('logout', 'LoginController@logout');
    Route::get('config', 'DeviceSettingsController@getConfig');

    Route::group(['middleware' => 'access-token'], function () {

        // ============================================
        // COMMON ENDPOINTS (Both order types)
        // ============================================
        Route::group(['prefix' => 'common'], function () {
            Route::get('station/list', 'KDSCommonController@getStationList');
            Route::get('station/device', 'KDSCommonController@getDeviceStation');
            Route::post('station/device', 'KDSCommonController@getDeviceStation');
            Route::get('station/process/list', 'KDSCommonController@getStationProcessList');
            Route::get('order/menu/list', 'KDSCommonController@getMenuList');
            Route::get('order/{order_id}/details', 'KDSCommonController@getOrderDetails');
            Route::get('orders/by-station/{station_index}', 'KDSCommonController@getOrdersByStation');
        });

        // ============================================
        // FAST-FOOD SPECIFIC
        // ============================================
        Route::group(['prefix' => 'fastfood'], function () {
            Route::post('action', 'KDSFastFoodController@action');
            Route::get('order/list', 'KDSFastFoodController@getOrderList');
            Route::get('order/{order_id}', 'KDSFastFoodController@getOrder');
            Route::get('menu/list', 'KDSFastFoodController@getMenuList');
        });

        // ============================================
        // FINE-DINE SPECIFIC
        // ============================================
        Route::group(['prefix' => 'finedine'], function () {
            Route::post('action', 'KDSFineDineController@action');
            Route::get('order/list', 'KDSFineDineController@getOrderList');
            Route::get('order/{order_id}', 'KDSFineDineController@getOrder');
            Route::get('menu/list', 'KDSFineDineController@getMenuList');
            Route::get('item/{item_id}/movement-history', 'KDSFineDineController@getMovementHistory');
        });

        // ============================================
        // LEGACY ENDPOINTS (Backward compatibility)
        // ============================================
        Route::get('station/list', 'KitchenStationController@list');
        Route::get('station/device', 'KitchenStationController@device');
        Route::post('station/device', 'KitchenStationController@device');
        Route::get('station/process/list', 'KitchenStationController@stationProcessList');
        Route::get('order/menu/list', 'KitchenDisplayController@getMenuList');
        Route::post('order/menu/move-station', 'KitchenDisplayController@moveMenu');
        Route::delete('order/remove', 'KitchenDisplayController@removeOrder');
        Route::delete('menu/remove', 'KitchenDisplayController@removeMenu');
        Route::post('order/move', 'KitchenDisplayController@moveOrder');
        Route::post('menu/move', 'KitchenDisplayController@moveMenu');
        Route::post('item/move', 'KitchenDisplayController@moveItem');
        Route::post('order/done', 'KitchenDisplayController@doneOrder');
        Route::post('menu/done', 'KitchenDisplayController@doneMenu');
        Route::post('order/release', 'KitchenDisplayController@releaseOrder');
        Route::post('menu/release', 'KitchenDisplayController@releaseMenu');

        // ============================================
        // DEVICE SETTINGS (Shared)
        // ============================================
        Route::post('device-settings/update', 'DeviceSettingsController@update');
        Route::post('device-settings/status', 'DeviceSettingsController@status');
        Route::get('device-settings/list', 'DeviceSettingsController@list');
        Route::get('device-settings/config', 'DeviceSettingsController@getConfig');

        // ============================================
        // BROADCASTING AUTH (Shared)
        // ============================================
        Route::post('broadcasting/auth', 'PusherAuthenticateController@authChannel');
    });
});

Route::group([
    'prefix' => 'kiosk/v1',
    'namespace' => 'KIOSK\v1'
], function () {
    Route::post('login', [\App\Http\Controllers\KIOSK\v1\LoginController::class, 'login']);
    Route::post('logout', [\App\Http\Controllers\KIOSK\v1\LoginController::class, 'logout']);
    Route::post('trigger-event', [\App\Http\Controllers\KIOSK\v1\EventTriggerController::class, 'trigger']);

    Route::group(['middleware' => 'pos-token'], function () {
        Route::post('item-availability/store', [\App\Http\Controllers\KIOSK\v1\ItemAvailabilityController::class, 'store']);
        Route::get('item-availability/list', [\App\Http\Controllers\KIOSK\v1\ItemAvailabilityController::class, 'list']);

        Route::post('transaction/store', [\App\Http\Controllers\KIOSK\v1\TerminalTransactionController::class, 'store']);
        Route::post('transaction/update', [\App\Http\Controllers\KIOSK\v1\TerminalTransactionController::class, 'update']);
        Route::post('transaction/search', [\App\Http\Controllers\KIOSK\v1\TerminalTransactionController::class, 'search']);
        Route::get('transaction/list', [\App\Http\Controllers\KIOSK\v1\TerminalTransactionController::class, 'list']);

        Route::post('device-settings/device-status', [\App\Http\Controllers\KIOSK\v1\DeviceSettingsController::class, 'deviceStatus']);
        Route::post('device-settings/print-status', [\App\Http\Controllers\KIOSK\v1\DeviceSettingsController::class, 'devicePrintStatus']);
        Route::get('device-settings/list', [\App\Http\Controllers\KIOSK\v1\DeviceSettingsController::class, 'list']);
    });
});

// This group of endpoints for FINE-DINE OTS integration, which will be used by the OTS system to send transaction data to Catapult. 
Route::group([
    'prefix' => 'station-ots/v1',
    'namespace' => 'StationOTS\v1'
], function () {
    Route::group(['middleware' => 'pos-token'], function () {
        Route::post('transaction/store', [\App\Http\Controllers\StationOTS\v1\TransactionController::class, 'store']);
        Route::post('transaction/check', [\App\Http\Controllers\POS\v1\TableManagementController::class, 'checkTransactionAvailability']);
        Route::post('table/check', [\App\Http\Controllers\POS\v1\TableManagementController::class, 'checkTableAvailability']);
        Route::post('table/list', [\App\Http\Controllers\POS\v1\TableManagementController::class, 'getTables']);
        Route::post('location/list', [\App\Http\Controllers\POS\v1\TableManagementController::class, 'getLocations']);
    });
});

Route::group([
    'prefix' => 'pos/v1',
    'namespace' => 'POS\v1'
], function () {
    Route::post('login', [\App\Http\Controllers\POS\v1\LoginController::class, 'login']);
    Route::post('logout', [\App\Http\Controllers\POS\v1\LoginController::class, 'logout']);

    Route::group(['middleware' => 'pos-token'], function () {
        Route::post('item-availability/store', [\App\Http\Controllers\POS\v1\ItemAvailabilityController::class, 'store']);
        Route::get('item-availability/list', [\App\Http\Controllers\POS\v1\ItemAvailabilityController::class, 'list']);

        Route::post('transaction/store', [\App\Http\Controllers\POS\v1\TerminalTransactionController::class, 'store']);
        Route::post('transaction/update', [\App\Http\Controllers\POS\v1\TerminalTransactionController::class, 'update']);
        Route::post('transaction/search', [\App\Http\Controllers\POS\v1\TerminalTransactionController::class, 'search']);
        Route::post('transaction/print-receipt', [\App\Http\Controllers\POS\v1\TerminalTransactionController::class, 'printReceipt']);
        Route::get('transaction/list', [\App\Http\Controllers\POS\v1\TerminalTransactionController::class, 'list']);

        Route::post('table/list', [\App\Http\Controllers\POS\v1\TableManagementController::class, 'getTables']);
        Route::post('location/list', [\App\Http\Controllers\POS\v1\TableManagementController::class, 'getLocations']);
        Route::post('table', [\App\Http\Controllers\POS\v1\TableManagementController::class, 'upsertTable']);
        Route::post('location', [\App\Http\Controllers\POS\v1\TableManagementController::class, 'upsertLocation']);
        Route::post('table/availability', [\App\Http\Controllers\POS\v1\TableManagementController::class, 'updateTableAvailability']);
        Route::post('table/check', [\App\Http\Controllers\POS\v1\TableManagementController::class, 'checkTableAvailability']);
        Route::post('transaction/availability', [\App\Http\Controllers\POS\v1\TableManagementController::class, 'updateTransactionAvailability']);
        Route::post('transaction/check', [\App\Http\Controllers\POS\v1\TableManagementController::class, 'checkTransactionAvailability']);

        Route::post('device-settings/device-status', [\App\Http\Controllers\POS\v1\DeviceSettingsController::class, 'deviceStatus']);
        Route::post('device-settings/print-status', [\App\Http\Controllers\POS\v1\DeviceSettingsController::class, 'devicePrintStatus']);
        Route::get('device-settings/list', [\App\Http\Controllers\POS\v1\DeviceSettingsController::class, 'list']);
    });
});


Route::group([
    'prefix' => 'sce/v1',
    'namespace' => 'SCE\v1'
], function () {
    Route::post('login', [\App\Http\Controllers\SCE\v1\LoginController::class, 'login']);
    Route::post('logout', [\App\Http\Controllers\SCE\v1\LoginController::class, 'logout']);
});


Route::post('/broadcast-event', [App\Http\Controllers\BroadcastController::class, 'broadcastEvent']);
Route::post('/broadcast-event/ots', [App\Http\Controllers\BroadcastController::class, 'broadcastOTSRequestStatusEvent']);
