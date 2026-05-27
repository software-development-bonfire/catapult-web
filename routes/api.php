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

// ============================================
// KDS MOBILE MONITOR
// ============================================
Route::group([
    'prefix' => 'kds-mobile/v1',
    'namespace' => 'KDSMobile\v1'
], function () {
    Route::post('login', [\App\Http\Controllers\KDSMobile\v1\KDSMobileLoginController::class, 'login']);
    Route::post('logout', [\App\Http\Controllers\KDSMobile\v1\KDSMobileLoginController::class, 'logout']);
    Route::get('config', [\App\Http\Controllers\KDSMobile\v1\KDSMobileController::class, 'config']);

    Route::group(['middleware' => 'access-token'], function () {
        Route::get('transactions', [\App\Http\Controllers\KDSMobile\v1\KDSMobileController::class, 'transactions']);
        Route::get('transactions/{transactionId}', [\App\Http\Controllers\KDSMobile\v1\KDSMobileController::class, 'transactionDetail']);
        Route::get('summary', [\App\Http\Controllers\KDSMobile\v1\KDSMobileController::class, 'summary']);
        Route::get('branches', [\App\Http\Controllers\KDSMobile\v1\KDSMobileController::class, 'branches']);
        Route::post('broadcasting/auth', [\App\Http\Controllers\KDSMobile\v1\KDSMobilePusherAuthController::class, 'authChannel']);
    });
});

Route::group([
    'prefix' => 'kds/v1',
    'namespace' => 'KDS\v1'
], function () {
    // ============================================
    // AUTH (Shared)
    // ============================================
    Route::post('login', [\App\Http\Controllers\KDS\v1\LoginController::class, 'login']);
    Route::post('logout', [\App\Http\Controllers\KDS\v1\LoginController::class, 'logout']);
    Route::get('config', [\App\Http\Controllers\KDS\v1\DeviceSettingsController::class, 'getConfig']);

    Route::group(['middleware' => 'access-token'], function () {

        // ============================================
        // UNIFIED KDS ENDPOINTS
        // ============================================
        Route::post('action', [\App\Http\Controllers\KDS\v1\KitchenDisplayController::class, 'action']);
        Route::post('move-item', [\App\Http\Controllers\KDS\v1\KitchenDisplayController::class, 'moveItem']);
        Route::post('move-order', [\App\Http\Controllers\KDS\v1\KitchenDisplayController::class, 'moveOrder']);

        // ============================================
        // LEGACY ENDPOINTS (Backward compatibility)
        // ============================================
        Route::get('station/list', [\App\Http\Controllers\KDS\v1\KitchenStationController::class, 'list']);
        Route::get('station/device', [\App\Http\Controllers\KDS\v1\KitchenStationController::class, 'device']);
        Route::post('station/device', [\App\Http\Controllers\KDS\v1\KitchenStationController::class, 'device']);
        Route::get('station/process/list', [\App\Http\Controllers\KDS\v1\KitchenStationController::class, 'stationProcessList']);
        Route::get('order/menu/list', [\App\Http\Controllers\KDS\v1\KitchenDisplayController::class, 'getMenuList']);
        Route::post('order/menu/move-station', [\App\Http\Controllers\KDS\v1\KitchenDisplayController::class, 'moveMenu']);
        Route::delete('order/remove', [\App\Http\Controllers\KDS\v1\KitchenDisplayController::class, 'removeOrder']);
        Route::delete('menu/remove', [\App\Http\Controllers\KDS\v1\KitchenDisplayController::class, 'removeMenu']);
        Route::post('order/move', [\App\Http\Controllers\KDS\v1\KitchenDisplayController::class, 'moveOrder']);
        Route::post('menu/move', [\App\Http\Controllers\KDS\v1\KitchenDisplayController::class, 'moveMenu']);
        Route::post('item/move', [\App\Http\Controllers\KDS\v1\KitchenDisplayController::class, 'moveItem']);
        Route::post('order/done', [\App\Http\Controllers\KDS\v1\KitchenDisplayController::class, 'doneOrder']);
        Route::post('menu/done', [\App\Http\Controllers\KDS\v1\KitchenDisplayController::class, 'doneMenu']);
        Route::post('order/release', [\App\Http\Controllers\KDS\v1\KitchenDisplayController::class, 'releaseOrder']);
        Route::post('menu/release', [\App\Http\Controllers\KDS\v1\KitchenDisplayController::class, 'releaseMenu']);

        // ============================================
        // DEVICE SETTINGS (Shared)
        // ============================================
        Route::post('device-settings/update', [\App\Http\Controllers\KDS\v1\DeviceSettingsController::class, 'update']);
        Route::post('device-settings/status', [\App\Http\Controllers\KDS\v1\DeviceSettingsController::class, 'status']);
        Route::get('device-settings/list', [\App\Http\Controllers\KDS\v1\DeviceSettingsController::class, 'list']);
        Route::get('device-settings/config', [\App\Http\Controllers\KDS\v1\DeviceSettingsController::class, 'getConfig']);

        // ============================================
        // SUMMARY
        // ============================================
        Route::post('kds-summary', [\App\Http\Controllers\KDS\v1\KdsSummaryController::class, 'summary']);

        // ============================================
        // KITCHEN PRINTER (KDS-triggered)
        // ============================================
        Route::post('printer/print-order', [\App\Http\Controllers\KDS\v1\KitchenPrinterController::class, 'printOrder']);
        Route::post('printer/print-bump-item', [\App\Http\Controllers\KDS\v1\KitchenPrinterController::class, 'printBumpItem']);
        Route::post('printer/print-table-no', [\App\Http\Controllers\KDS\v1\KitchenPrinterController::class, 'printTableNumber']);

        // ============================================
        // BROADCASTING AUTH (Shared)
        // ============================================
        Route::post('broadcasting/auth', [\App\Http\Controllers\KDS\v1\PusherAuthenticateController::class, 'authChannel']);
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
