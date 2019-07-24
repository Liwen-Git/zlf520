<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/15
 * Time: 17:12
 */

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Modules\Setting\SettingService;
use App\Result;

class VersionController extends Controller
{

    /**
     * 获取最新版本
     */
    public function last()
    {
        $versionNo = request()->headers->get('version');
        if (empty($versionNo)) {
            $versionNo = request('version');
        }
        $current = SettingService::getValueByKey('miniprogram_current_version');
        $rt['status'] = 1;
        if ($versionNo > $current) {
            $rt['status'] = 0;
        }
        return Result::success($rt);
    }

}