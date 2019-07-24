<?php

namespace App\Http\Controllers\UserApp;


use App\DataCacheService;
use App\Exceptions\DataNotFoundException;
use App\Http\Controllers\Controller;
use App\Modules\Cs\CsActivityService;
use App\Modules\Cs\CsGoodService;
use App\Modules\Cs\CsMerchant;
use App\Modules\Cs\CsMerchantService;
use App\Modules\Cs\CsMerchantSettingService;
use App\Modules\Merchant\MerchantFollow;
use App\Modules\Setting\SettingService;
use App\Result;

class CsMerchantController extends Controller
{
    public function getList()
    {
        $supermarketOn = SettingService::getValueByKey('supermarket_on');
        if ($supermarketOn!=1) {
            return Result::success(['list' => [], 'total' => 0]);
        }
        $user_key = request()->get('current-device-no');
        if (empty($user_key)) {
            $user_key = request()->headers->get('token');
            if (empty($user_key)) {
                $user_key = request()->ip();
            }
        }

        $version = request()->header('version');

        if ($version >= '1.5.2') {

            $data = CsMerchantService::getListAndDistance([
                'city_id' => request('city_id'),
                'keyword' => request('keyword'),
                'lng' => request('lng'),
                'lat' => request('lat'),
                'radius' => request('radius'),
                'user_key' => $user_key,
                'onlyPayToPlatform' => 1,
                'pageSize' => request('pageSize', 15),
                'page'  =>  request('page'),
            ]);
        } else {
            $data = CsMerchantService::getListAndDistanceOld([
                'city_id' => request('city_id'),
                'keyword' => request('keyword'),
                'lng' => request('lng'),
                'lat' => request('lat'),
                'radius' => request('radius'),
                'user_key' => $user_key,
                'onlyPayToPlatform' => 1,
                'pageSize' => request('pageSize', 15),
            ]);
        }


        return Result::success($data);

    }


    public function otherCityList()
    {
        $supermarketOn = SettingService::getValueByKey('supermarket_on');
        if ($supermarketOn!=1) {
            return Result::success(['list' => [], 'total' => 0]);
        }
        $user_key = request()->get('current-device-no');
        if (empty($user_key)) {
            $user_key = request()->headers->get('token');
            if (empty($user_key)) {
                $user_key = request()->ip();
            }
        }

        $data = CsMerchantService::otherCityList([
            'city_id' => request('city_id'),
            'keyword' => request('keyword'),
            'onlyPayToPlatform' => 1,
            'pageSize' => request('pageSize', 15),
        ]);

        return Result::success($data);

    }

    public function getHotList()
    {
        $user_key = request()->get('current-device-no');
        if (empty($user_key)) {
            $user_key = request()->headers->get('token');
            if (empty($user_key)) {
                $user_key = request()->ip();
            }
        }

        $data = CsMerchantService::getHotSellListAndDistance([
            'keyword' => request('keyword'),
            'lng' => request('lng'),
            'lat' => request('lat'),
            'city_id' => request('city_id'),
            'radius' => request('radius'),
            'user_key' => $user_key,
            'onlyPayToPlatform' => 1,
            'pageSize' => request('pageSize', 15),
        ]);

        return Result::success($data);

    }

    public function getOtherHotList()
    {
        $user_key = request()->get('current-device-no');
        if (empty($user_key)) {
            $user_key = request()->headers->get('token');
            if (empty($user_key)) {
                $user_key = request()->ip();
            }
        }

        $data = CsMerchantService::getOtherHotSellList([
            'keyword' => request('keyword'),
            'city_id' => request('city_id'),
            'user_key' => $user_key,
            'onlyPayToPlatform' => 1,
            'pageSize' => request('pageSize', 15),
        ]);

        return Result::success($data);

    }

    /**
     * 获取超市商户全部分类
     */
    public function getCategoryTree(){
        $this->validate(request(),[
            'merchant_id' => 'required|integer|min:1'
        ]);
        $cs_merchant_id = request('merchant_id');
        $list = DataCacheService::getCsMerchantCats($cs_merchant_id);

        if ($list) {
            $platform_useful = DataCacheService::getPlatformCatsUseful();
            foreach ($list as $k1=>$v1) {
                if ($v1['cat_id_level1'] == 0) {
                    continue;
                }
                if (empty($platform_useful[$v1['cat_id_level1']])) {
                    unset($list[$k1]);
                    continue;
                }
                if (!empty($v1['sub'])) {
                    foreach ($v1['sub'] as $k2=>$v2) {
                        if ($v2['cat_id_level2'] == 0) {
                            continue;
                        }
                        if (empty($platform_useful[$v2['cat_id_level2']])) {
                            unset($list[$k1]['sub'][$k2]);
                            continue;
                        }
                    }

                    $v1['sub'] = array_values($v1['sub']);
                }
                $list[$k1]['hot_sell'] = 'off';
                $list[$k1]['cat_type'] = 1;
            }
            $list = array_values($list);
        }

        // 处理限时抢
        if( (request()->header('version') >= '1.5.4')){
            CsGoodService::addMarketingInfo($list,$cs_merchant_id);
        }

        // 处理拼团
        if( (request()->header('version') >= '1.5.7')){
            CsGoodService::addGroupBookCategories($list,$cs_merchant_id);
        }

        // 处理年货节
        if( (request()->header('version') >= '1.5.2') && CsActivityService::isOpenHotSell() ){
            CsGoodService::getHotSellListByCsMerchantId($list,$cs_merchant_id);
        }


        return Result::success(['list'=>$list]);
    }


    /**
     * 获取超市详情
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $this->validate(request(), [
            'merchant_id' => 'required|integer',
        ]);

        $userId = request()->get('current_user')->id ?? 0;

        $merchant_id = request('merchant_id');
        $detail = CsMerchantService::getById($merchant_id);
        if(!$detail||$detail->status!=CsMerchant::STATUS_ON||$detail->audit_status!=CsMerchant::AUDIT_STATUS_SUCCESS){
            throw new DataNotFoundException('商户异常');
        }

        // 补充配送信息
        $deliverSetting = CsMerchantSettingService::getDeliverSetting($detail->id);
        if($deliverSetting){
            $detail->delivery_start_price = $deliverSetting->delivery_start_price;
            $detail->delivery_charges = $deliverSetting->delivery_charges;
            $detail->delivery_free_start = $deliverSetting->delivery_charges == 0 ? 0 : $deliverSetting->delivery_free_start;
            $detail->delivery_free_order_amount = $deliverSetting->delivery_free_order_amount;
        }
        $detail->city_limit = SettingService::getValueByKey('supermarket_city_limit');
        $detail->show_city_limit = SettingService::getValueByKey('supermarket_show_city_limit');
        if(!isset($detail->distance)){
            $detail->distance = 0;//没有经纬度时,设置为0
        }

        //超市是否被当前用户关注
        $isFollows = MerchantFollow::where('merchant_id',$merchant_id)
            ->where('user_id',$userId)
            ->where('status',MerchantFollow::USER_YES_FOLLOW)
            ->where('merchant_type', MerchantFollow::MERCHANT_TYPE_CS)
            ->first();
        $detail->isFollows = empty($isFollows) ? MerchantFollow::USER_NOT_FOLLOW : MerchantFollow::USER_YES_FOLLOW;

        return Result::success($detail);
    }

    /**
     * 查询超市商户列表 通过商户名称关键字查询
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCsMerchantListByName()
    {
        $this->validate(request(), [
            'keyword' => 'required'
        ],[
            'keyword.required' => '关键词不能为空',
        ]);
        $name = request('keyword');
        $data = CsMerchantService::getListByName($name);

        return Result::success([
            'list' => $data->items(),
            'total' => $data->total(),
        ]);
    }
}
