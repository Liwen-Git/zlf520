<?php
/**
 * Created by PhpStorm.
 * User: tim.tang
 * Date: 2018/11/21/021
 * Time: 15:30
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Result;
use App\Modules\Cs\CsActivityService;

class CsActivityController extends Controller
{

    public function getList()
    {

        $list = CsActivityService::getList();
        return Result::success([
            'list' => $list,
        ]);
    }

    public function updateStatus()
    {
        $id = request('id', 0);
        $status = request('status', 2);

        CsActivityService::updateStatus($id, $status);

        return Result::success('更新成功');
    }

    public function getActivity()
    {

        $id = request('id', 0);
        $data = CsActivityService::getActivity($id);
        return Result::success($data);

    }

    public function saveActivity()
    {
        $data = [
            'id' => request('id', 0),
            'title' => request('title', ''),
            'logo' => request('logo', ''),
            'tag' => request('tag', ''),
            'remark' => request('remark', ''),
            'desc' => request('desc', ''),
            'pic_list' => request('pic_list', ''),
            'icon' => request('icon', ''),
            'triangle_icon' => request('triangle_icon', ''),
            'banner' => request('banner', ''),
            'start_ad' => request('start_ad', ''),
            'bottom_right_icon' => request('bottom_right_icon', ''),

        ];
        CsActivityService::saveActivity($data);
        return Result::success('更新成功');
    }

}
