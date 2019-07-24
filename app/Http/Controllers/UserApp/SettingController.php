<?php
/**
 * Created by PhpStorm.
 * User: tim.tang
 * Date: 2018/11/25/025
 * Time: 16:36
 */
namespace App\Http\Controllers\UserApp;

use App\Http\Controllers\Controller;
use App\Modules\Setting\SettingService;
use App\Result;

class SettingController extends Controller
{
    /**
     * app通用配置
     * @return mixed
     */
    public function settings()
    {

        return Result::success([
            'supermarket_on' => SettingService::getValueByKey('supermarket_on'),
            'index_cs_banner_on' => SettingService::getValueByKey('supermarket_index_cs_banner_on'),
//            'index_cs_banner_url' => 'https://daqian-public-1257640953.cos.ap-guangzhou.myqcloud.com/6352c1aecba7dd10fcc87c32bf3d92a1.png'
            'index_cs_banner_url' => 'https://daqian-public-1257640953.cos.ap-guangzhou.myqcloud.com/fe2bdb8fd93390a2b0b6a2f610e03dcb.png',
            'ybk_anchor_on' => SettingService::getValueByKey('ybk_anchor_on'),
            'supermarket_buy_one' => 1,//超市 限制只能购买一个商品开关 1打开
            'supermarket_buy_one_toast' => '因过年期间物流系统升级，每个订单限制购买一种商品，望理解', //提示语
        ]);
    }
}