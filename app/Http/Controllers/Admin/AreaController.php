<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/11
 * Time: 15:54
 */

namespace App\Http\Controllers\Admin;

use App\Modules\Area\AreaService;
use App\Result;

class AreaController
{
    /**
     * 获取树形地区表
     */
    public function getTree()
    {
        $tier = request('tier', 3);
        return Result::success(['list' => AreaService::getAsTree($tier)]);
    }

}