<?php


namespace App\Http\Controllers;


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
    public function upload()
    {
        $file = request()->file('file');

        $disk = QiniuStorage::disk('qiniu');
        $directory = request('directory', 'blog');
        $path = $disk->put($directory, $file);
        $url = 'http://' . env('QINIU_DOMAINS') . '/' . $path;

        return Result::success([
            'url' => $url,
        ]);
    }
}