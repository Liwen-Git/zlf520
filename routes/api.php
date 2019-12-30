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
        Route::post('/qiniu/upload', 'QiNiuController@upload');
        Route::post('/qiniu/upload/delete', 'QiNiuController@delete');

        // 本地上传
        Route::post('/local/upload', 'UploadController@upload');
        Route::post('/local/upload/delete', 'UploadController@delete');

        // 图片列表
        Route::get('/qiniu/image/list', 'ImageController@getList');
    });

Route::prefix('mini')
    ->group(function () {
        Route::get('/story/check', 'StoryController@checkPassword');
    });