<?php


namespace App\Http\Controllers;


use App\Http\Services\ImageService;
use App\Result;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use zgldh\QiniuStorage\QiniuStorage;

class UploadController extends Controller
{
    /**
     * 上传
     * @return ResponseFactory|Response
     */
    public function upload()
    {
        $file = request()->file('file');

        $directory = request('directory', 'blog');
        // 上传
        $path = $file->store('public/'. $directory);
        $path_storage = 'storage' . substr($path, 6);
        $url = asset($path_storage);

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

        $res = Storage::delete($detail->qiniu_url);

        if ($res) {
            ImageService::deleteById($id);
        }

        return Result::success([
            'status' => $res,
        ]);
    }
}