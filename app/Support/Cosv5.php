<?php
/**
 * Created by PhpStorm.
 * User: 57458
 * Date: 2019/1/12
 * Time: 14:20
 */

namespace App\Support;


use App\Exceptions\BaseResponseException;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Image;

class Cosv5
{

    /**
     * @param Image $image
     * @param string $filename
     * @return string
     */
    public static function storeImageToCos($image, $filename)
    {
        if(! Storage::disk('cosv5')->put($filename, $image->encode('png')->getEncoded()) ){
            throw new BaseResponseException('cos图片存储失败');
        }
        $url = config('cos.cos_url') . $filename;
        return $url;
    }
}