<?php


namespace App\Http\Controllers;


use App\Http\Services\ImageService;
use App\Result;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use zgldh\QiniuStorage\QiniuStorage;

class QiNiuController extends Controller
{
    /**
     * 七牛上传
     * @return ResponseFactory|Response
     */
    public function uploadImage()
    {
        $file = request()->file('file');

        $disk = QiniuStorage::disk('qiniu');
        $directory = request('directory', 'blog');
        // 上传到七牛云
        $path = $disk->put($directory, $file);
        $url = 'http://' . env('QINIU_DOMAINS') . '/' . $path;
        // 添加数据库
        ImageService::add($directory, $url);

        return Result::success([
            'url' => $url,
        ]);
    }
}