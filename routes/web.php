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
use Illuminate\Support\Facades\Route;

Route::get('/', 'LoginController@index');
Route::post('/login', 'LoginController@login')->name('login');
Route::post('/logout', 'LoginController@logout')->name('logout');

Route::group(['middleware' => 'auth'], function () {
    Route::get('/dashboard', 'DashboardController@index');
    Route::get('/dashboard/summary', 'DashboardController@summary');
    Route::get('/configurations', 'ConfigurationsController@view');
    Route::get('/syncing-setup', 'SyncingSetupController@view');
    Route::get('/sync-interval-setting', 'SyncIntervalSettingController@view');
    Route::get('/user-account', 'UserAccountController@view');
    Route::get('/field-mapping', 'FieldMappingController@view');
    Route::get('/field-mapping/detail', 'FieldMappingController@detail');
    Route::get('/logs', 'LogsController@view');

    // Item Availability
    Route::group(['prefix' => 'item-availability'], function () {
        Route::get('/', 'ItemAvailabilityController@index');
        Route::get('/list', 'ItemAvailabilityController@list');
        Route::patch('/update', 'ItemAvailabilityController@update');
    });

    // Field Mapping Preset
    Route::group(['prefix' => 'field-mapping-preset'], function () {
        Route::get('/', 'FieldMappingPresetController@view');
        Route::get('/list', 'FieldMappingPresetController@list');
        Route::get('/detail', 'FieldMappingPresetController@detail');
        Route::get('/detail/preset', 'FieldMappingPresetController@index');
        Route::post('/detail/preset', 'FieldMappingPresetController@storePreset');
        Route::post('/store', 'FieldMappingPresetController@store');
        Route::post('/details', 'FieldMappingPresetController@storeDetails');
        Route::patch('/update/{bid}', 'FieldMappingPresetController@update');
        Route::post('/detail/store', 'FieldMappingPresetController@storeDetail');
        Route::patch('/detail/update/{bid}', 'FieldMappingPresetController@updateDetail');
        Route::delete('/{bid}', 'FieldMappingPresetController@destroy');
        Route::delete('/detail/{bid}', 'FieldMappingPresetController@destroyDetail');
    });

    Route::group(['prefix' => 'field-mapping'], function () {
        Route::group(['prefix' => 'detail'], function () {
            Route::get('/get-data-entries', 'FieldMappingController@getDataEntries');
            Route::post('/store', 'FieldMappingController@storeDataMapping');
            Route::patch('/update/{bid}', 'FieldMappingController@updateDataMapping');
            Route::get('/get-list', 'FieldMappingController@getList');
            Route::get('/data-mapping-list', 'FieldMappingController@dataMappingList');
            Route::get('/generate-csv', 'FieldMappingController@generateCsv');
        });
        Route::patch('/update/{bid}', 'FieldMappingController@update');
        Route::get('/list', 'FieldMappingController@list');
        Route::post('/store', 'FieldMappingController@store');
    });

    Route::group(['prefix' => 'terminal-file-setup'], function () {
        Route::patch('/update/{bid}', 'TerminalFileSetupController@update');
        Route::delete('/destroy/{bid}', 'TerminalFileSetupController@destroy');
        Route::get('/list', 'TerminalFileSetupController@index');
        Route::post('/store', 'TerminalFileSetupController@store');
        Route::get('/chosen/endpoints', 'TerminalFileSetupController@getEndpointChosen');
    });

    Route::group(['prefix' => 'device-settings'], function () {
        Route::get('/list', 'DeviceSettingsController@index');
        Route::post('/store', 'DeviceSettingsController@store');
        Route::patch('/update', 'DeviceSettingsController@update');
        Route::delete('/delete', 'DeviceSettingsController@destroy');
    });

    Route::apiResources([
        'file-storage-setup' => 'FileStorageSetupController',
        'catapult-db-setup' => 'CatapultDbSetupController',
        'api-setup' => 'ApiSetupController',
        'syncing' => 'SyncingSetupController',
        'user' => 'UserAccountController',
        'field-mapping-list' => 'FieldMappingController',
    ]);


});
Route::get('/sync-entry/chosen', 'SyncEntryController@getChosen');
Route::get('/{any_path?}', [HomeController::class, 'index'])->where('any_path', '(.*)');
