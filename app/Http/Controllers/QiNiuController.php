<?php


namespace App\Http\Controllers;


use App\Http\Services\ImageService;
use App\Result;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
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
        // 上传到七牛云
        $path = $disk->put($directory, $file);
        $url = 'http://' . env('QINIU_DOMAINS') . '/' . $path;
        // 添加数据库
        $data = [
            'directory' => $directory,
            'url' => $url,
            'qiniu_url' => $path,
            'type' => request('type', 1),
        ];
        ImageService::add($data);

        return Result::success([
            'url' => $url,
        ]);
    }

    /**
     * @return ResponseFactory|Response
     * @throws ValidationException
     */
    public function delete()
    {
        $this->validate(request(), [
            'id' => 'required',
        ]);
        $id = request('id');
        $detail = ImageService::getById($id);

        $disk = QiniuStorage::disk('qiniu');
        $res = $disk->delete($detail->qiniu_url);

        return Result::success([
            'status' => $res,
        ]);
    }
}