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
}