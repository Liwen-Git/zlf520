<?php


namespace App\Http\Services;


use zgldh\QiniuStorage\QiniuStorage;

class QiNiuService extends BaseService
{
    /**
     * 取得目录下所有文件
     * @param $directory
     * @return mixed
     */
    public static function getAllFiles($directory)
    {
        $disk = QiniuStorage::disk('qiniu');
        $list = $disk->files($directory);

        return $list;
    }

    /**
     * 获取文件内容
     * @param $path
     * @return mixed
     */
    public static function getFile($path)
    {
        $disk = QiniuStorage::disk('qiniu');
        $file = $disk->get($path);

        return $file;
    }
}