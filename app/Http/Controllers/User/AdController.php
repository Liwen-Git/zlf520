<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/12
 * Time: 16:36
 */

namespace App\Http\Controllers\User;


use App\Exceptions\BaseResponseException;
use App\Modules\Ad\AdPositionService;
use App\Modules\Ad\AdService;
use App\Support\MarketingApi;
use App\Result;

class AdController
{

    public function getByPosition()
    {
        $positionCode = request('position');
        $position = AdPositionService::getByCode($positionCode);
        if(empty($position)){
//            throw new BaseResponseException('广告位不存在');
            return Result::success('请求成功', ['list' => []]);
        }
        if($position['status'] != 1){
            // 广告位已禁用
            return Result::success('请求成功', ['list' => []]);
        }
        $ads = AdService::getEnableListByPositionCode($positionCode);
        foreach ($ads as $ad) {
            $ad->payload = json_decode($ad->payload, 1);
        }
        return Result::success(['list' => $ads]);
    }
}
