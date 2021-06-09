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
});

Route::get('/configurations', 'ConfigurationsController@index');
Route::get('/syncing-setup', 'SyncingSetupController@index');

Route::get('/{any_path?}', [HomeController::class, 'index'])->where('any_path', '(.*)');