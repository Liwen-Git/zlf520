<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/20
 * Time: 8:57
 */
namespace App\Http\Controllers\User;

use App\Exceptions\BaseResponseException;
use App\Http\Controllers\Controller;
use App\Modules\Ybk\YbkAnchorService;
use App\Result;

class YbkController extends Controller
{
    /**
     * 搜索云博客主播信息
     */
    public function searchAnchor()
    {
        $keyword = request('keyword');
        $keyword = trim($keyword);
        if (empty($keyword) && !is_numeric($keyword)) {
            throw new BaseResponseException('参数错误');
        }

        $list = YbkAnchorService::search($keyword);

        return Result::success($list);

    }
}