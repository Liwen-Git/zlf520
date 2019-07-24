<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\DataNotFoundException;
use App\Modules\Ad\Ad;
use App\Modules\Ad\AdPositionService;
use App\Modules\Ad\AdService;
use App\Result;
use App\Http\Controllers\Controller;

class AdController extends Controller
{
    public function getList()
    {
        $list = AdService::getList();
        return Result::success([
            'list' => $list,
        ]);
    }

    public function add()
    {
        $this->validate(request(), [
            'positionId' => 'required|integer|min:1',
            'name' => 'required'
        ]);
        $positionId = request('positionId')->post('positionId');
        $name = request()->post('name');
        $image = request()->post('image');
        $desc = request()->post('desc');
        $linkType = request()->post('linkType');
        $payload = request()->post('payload');
        $status = request()->post('status');

        $position = AdPositionService::getById($positionId);
        if(empty($position)){
            throw new DataNotFoundException('广告位不存在');
        }

        $ad = new Ad();
        $ad->name = $name;
        $ad->position_id = $positionId;
        $ad->position_code = $position->code;
        $ad->image = $image;
        $ad->desc = $desc;
        $ad->link_type = $linkType;
        $ad->payload = $payload;
        $ad->status = $status;

        $ad->save();

        return Result::success($ad);
    }
}
