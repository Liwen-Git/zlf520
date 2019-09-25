<?php


namespace App\Http\Services;

use App\Http\Modules\Image;

class ImageService extends BaseService
{
    /**
     * 图片添加
     * @param $directory
     * @param $url
     * @return Image
     */
    public static function add($directory, $url)
    {
        $image = new Image();
        $image->directory = $directory;
        $image->url = $url;
        $image->save();

        return $image;
    }
}