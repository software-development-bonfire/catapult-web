<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use App\Http\Controllers\HomeController;



Route::get('/', 'LoginController@index');
Route::post('/login', 'LoginController@login')->name('login');
Route::post('/logout', 'LoginController@logout')->name('logout');

Route::group(['middleware' => 'auth'], function () {
    Route::get('/dashboard', 'DashboardController@index');
    Route::get('/configurations', 'ConfigurationsController@view');
    Route::get('/syncing-setup', 'SyncingSetupController@view');
    Route::get('/sync-interval-setting', 'SyncIntervalSettingController@view');
    Route::get('/user-account', 'UserAccountController@view');

    // Field Mapping
    Route::group(['prefix' => 'field-mapping-setup'], function () {
        Route::get('/', 'FieldMappingSetupController@view');
        Route::get('/index', 'FieldMappingSetupController@index');
        Route::get('/detail', 'FieldMappingSetupController@detail');
        Route::get('/detail/preset', 'FieldMappingSetupController@index');
        Route::post('/detail/preset', 'FieldMappingSetupController@storePreset');
        Route::post('/detail', 'FieldMappingSetupController@store');
        Route::post('/details', 'FieldMappingSetupController@storeDetails');
        Route::put('/detail/{bid}', 'FieldMappingSetupController@update');
        Route::post('/detail-create', 'FieldMappingSetupController@detailCreate');
        Route::put('/detail-update/{bid}', 'FieldMappingSetupController@detailUpdate');
        Route::delete('/{bid}', 'FieldMappingSetupController@destroy');
        Route::delete('/detail_delete/{bid}', 'FieldMappingSetupController@detailDestroy');
    });

    Route::apiResources([
        'remote-setup' => 'RemoteSetupController',
        'catapult-db-setup' => 'CatapultDbSetupController',
        'api-setup' => 'ApiSetupController',
        'syncing' => 'SyncingSetupController',
    ]);
});

Route::get('/{any_path?}', [HomeController::class, 'index'])->where('any_path', '(.*)');