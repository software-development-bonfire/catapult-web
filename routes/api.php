<?php

use Illuminate\Http\Request;
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
    Route::post('login', [\App\Http\Controllers\KDS\v1\LoginController::class, 'login']);
    Route::post('logout', [\App\Http\Controllers\KDS\v1\LoginController::class, 'logout']);

    Route::group(['middleware' => 'access-token'], function () {
        Route::get('station/list', 'KitchenStationController@list');
        Route::get('station/process/list', 'KitchenStationController@stationProcessList');
        Route::get('order/menu/list', 'KitchenDisplayController@getMenuList');
        Route::post('order/menu/move-station', 'KitchenDisplayController@moveMenu');
        Route::delete('order/remove', 'KitchenDisplayController@removeOrder');
        Route::delete('menu/remove', 'KitchenDisplayController@removeMenu');
    });
});

Route::group([
    'prefix' => 'pos/v1',
    'namespace' => 'POS\v1'
], function () {
    Route::post('login', [\App\Http\Controllers\POS\v1\LoginController::class, 'login']);
    Route::post('logout', [\App\Http\Controllers\POS\v1\LoginController::class, 'logout']);

    Route::group(['middleware' => 'pos-token'], function () {
        Route::post('item-availability/store',  [\App\Http\Controllers\POS\v1\ItemAvailabilityController::class, 'store']);
        Route::get('item-availability/list',  [\App\Http\Controllers\POS\v1\ItemAvailabilityController::class, 'list']);
        
        Route::post('transaction/store',  [\App\Http\Controllers\POS\v1\TerminalTransactionController::class, 'store']);
        Route::post('transaction/update',  [\App\Http\Controllers\POS\v1\TerminalTransactionController::class, 'update']);
        Route::post('transaction/search',  [\App\Http\Controllers\POS\v1\TerminalTransactionController::class, 'search']);
        Route::get('transaction/list',  [\App\Http\Controllers\POS\v1\TerminalTransactionController::class, 'list']);
    });
});
