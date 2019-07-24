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

use App\Exceptions\BaseResponseException;
use App\Exceptions\ParamInvalidException;
use App\Modules\Wechat\MiniprogramScene;
use App\Modules\Wechat\WechatService;

Route::get('/', function () {
    return redirect('/admin');
});

// 后端页面
Route::view('/admin', 'admin');

Route::post('/upload/image', 'UploadController@image');
Route::get('/download', 'DownloadController@download');

// 微信认证路由
Route::get('/{name}.txt', function ($name) {
    $file = \App\Modules\Wechat\WxVerifyFile::find($name);
    if(empty($file)){
        abort(404);
    }
    // 优化, 文件存在时写入到public目录, 下次不需要重新走程序
    file_put_contents(public_path($name . '.txt'), $file->content);

    exit($file->content);
});
