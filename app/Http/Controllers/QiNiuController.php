<?php


namespace App\Http\Controllers;


use App\Result;
use zgldh\QiniuStorage\QiniuStorage;

class QiNiuController extends Controller
{
    public function upload()
    {
        $file = request()->file('file');

        $disk = QiniuStorage::disk('qiniu');
        $directory = 'blog';
        $path = $disk->put($directory, $file);
        $url = 'http://' . env('QINIU_DOMAINS') . '/' . $path;

        return Result::success([
            'url' => $url,
        ]);
    }
}