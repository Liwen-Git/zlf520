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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('admin')
    ->group(function() {
        // 登录 登出
        Route::post('/login', 'LoginController@login');
        Route::post('/logout', 'LoginController@logout');

        // 七牛
        Route::post('/qiniu/upload/image', 'QiNiuController@uploadImage');
        Route::get('/qiniu/image/list', 'ImageController@getList');
    });
