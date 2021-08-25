<?php

use Illuminate\Http\Request;

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

    Route::group(['middleware' => 'access-token'], function () {
        Route::get('station/list', 'KitchenStationController@list');
        Route::get('station/process/list', 'KitchenStationController@stationProcessList');
        Route::get('order/menu/list', 'KitchenDisplayController@getMenuList');
        Route::post('order/menu/move-station', 'KitchenDisplayController@moveMenu');
        Route::delete('order/remove', 'KitchenDisplayController@removeOrder');
        Route::delete('menu/remove', 'KitchenDisplayController@removeMenu');
    });
});
