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

Route::group(['middleware' => 'hasher', 'prefix' => 'v1'], function () {
    Route::group(['prefix' => 'token'], function () {
        Route::post('/get', 'Api\TokenController@getToken')->name('token.get');
        Route::post('/refresh', 'Api\TokenController@refreshToken')->name('token.refresh');
        Route::post('/validate', 'Api\TokenController@validateToken')->name('token.validate');
    });
});
