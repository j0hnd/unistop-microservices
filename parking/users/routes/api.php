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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['middleware' => 'api', 'prefix' => 'v1'], function () {
    Route::group(['prefix' => 'users'], function () {
        Route::post('login.json', 'Api\UserController@login')->name('user.login');
        Route::get('logout.json', 'Api\UserController@logout')->name('user.logout');
        Route::get('profile.json', 'Api\UserController@profile')->name('user.profile');
    });

    Route::group(['prefix' => 'users/password'], function () {
        Route::post('forgot.json', 'Api\UserController@forgot')->name('user.password.forgot');
    });
});
