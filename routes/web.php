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
    Route::get('/configurations', 'ConfigurationsController@view');
    Route::get('/syncing-setup', 'SyncingSetupController@view');
    Route::get('/sync-interval-setting', 'SyncIntervalSettingController@view');
    Route::get('/user-account', 'UserAccountController@view');
    Route::get('/field-mapping', 'FieldMappingController@view');
    Route::get('/field-mapping/detail', 'FieldMappingController@detail');
    Route::get('/logs', 'LogsController@view');

    // Field Mapping Setup
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

    Route::group(['prefix' => 'field-mapping/detail'], function () {
        Route::get('/get-endpoint', 'FieldMappingController@getEndpoint');
        Route::post('/data-mapping', 'FieldMappingController@storeDataMapping');
        Route::put('/data-mapping/{field_mapping_list_bid}', 'FieldMappingController@updateDataMapping');
        Route::get('/get-list', 'FieldMappingController@getList');
        Route::resource('/list', 'FieldMappingController');
    });

    Route::apiResources([
        'remote-setup' => 'RemoteSetupController',
        'catapult-db-setup' => 'CatapultDbSetupController',
        'api-setup' => 'ApiSetupController',
        'syncing' => 'SyncingSetupController',
        'user' => 'UserAccountController',
        'field-mapping-list' => 'FieldMappingController',
    ]);
});

Route::get('/{any_path?}', [HomeController::class, 'index'])->where('any_path', '(.*)');