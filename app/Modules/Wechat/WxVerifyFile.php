<?php
/**
 * Created by PhpStorm.
 * User: 57458
 * Date: 2019/1/14
 * Time: 16:06
 */

namespace App\Modules\Wechat;


use App\BaseModel;

/**
 * Class WxVerifyFile
 * @package App\Modules\Wechat
 * @property string $name
 * @property string $content
 */
class WxVerifyFile extends BaseModel
{
    public $incrementing = false;
    protected $primaryKey = 'name';
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'name', 'content',
    ];
}