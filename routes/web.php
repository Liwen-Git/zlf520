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

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// 后台页面
Route::view('/admin', 'admin');

// laravel blade 的页面
Route::view('/love/the_red_heart', 'welcome');

// wechat路由
Route::any('/wechat', 'WeChatController@serve');