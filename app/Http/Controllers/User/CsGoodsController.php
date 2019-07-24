<?php

namespace App\Http\Controllers\User;

use App\Exceptions\DataNotFoundException;
use App\Http\Controllers\Controller;
use App\Modules\Goods\GoodsService;
use App\Support\MarketingApi;
use App\Modules\Cs\CsGood;
use App\Modules\Cs\CsGoodService;
use App\Result;

class CsGoodsController extends Controller
{
    /**
     * 获取商户下商品
     */
    public function getAllGoods(){
        $this->validate(request(),[
            'merchant_id' => 'required|integer|min:1'
        ]);

        $isSale = request('is_sale');  //是否销量1升序 2降序
        $isPrice = request('is_price');//是否价格1升序 2降序

        $params['cs_merchant_id'] = request('merchant_id',0);
        $params['goods_name'] = request('goods_name','');
        $params['cs_platform_cat_id_level1'] = request('first_level_id','');
        $params['cs_platform_cat_id_level2'] = request('second_level_id','');
        $params['status'] = [CsGood::STATUS_ON];
        $params['audit_status'] =[CsGood::AUDIT_STATUS_SUCCESS];
        $params['pageSize'] = request('pageSize',200);
        $params['hot_status'] = request('hot_status', CsGood::HOT_STATUS_OFF);

        if (!empty($isSale)) {
            $params['sort'] = 'sale_num';
            $params['order'] = $isSale == 1 ?'asc':'desc';
        } elseif (!empty($isPrice)) {
            $params['sort'] = 'sort_price';
            $params['order'] = $isPrice == 1 ?'asc':'desc';
        } elseif ($params['hot_status'] == CsGood::HOT_STATUS_ON) {
            $params['sort'] = 'hot_sort';
            $params['order'] = 'desc';
        } else {
            $params['sort'] = '1';
        }
        $data = CsGoodService::userClientGetList($params);
        return Result::success([
            'list' => $data->items(),
            'total' => $data->total(),
        ]);
    }

    /**
     * 超市商品详情
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $this->validate(request(), [
            'id' => 'required|integer|min:1'
        ]);
        $goods_id = request('id');
        $detail = CsGoodService::getById($goods_id);
        if(!$detail||$detail->status!=CsGood::STATUS_ON||$detail->audit_status!=CsGood::AUDIT_STATUS_SUCCESS)throw new DataNotFoundException('该商品或已下架');
        $merchant_id = $detail->cs_merchant_id;
        $goods_type = 3;

        $marketing_info_ori = MarketingApi::addMarketingInfo($merchant_id,$goods_type,$goods_id);
        $marketing_info = $marketing_info_ori['list'];
        $detail->marketing_started = (object) [];
        $detail->marketing_soon = (object) [];
        if (!empty($marketing_info[$goods_id]['marketing_started'])) {
            $detail->marketing_started->user_limit = $marketing_info[$goods_id]['marketing_started']['limited'];
            $detail->marketing_started->sales_count = $marketing_info[$goods_id]['marketing_started']['sales_count'];
            $detail->marketing_started->marketing_stock = $marketing_info[$goods_id]['marketing_started']['stock'];
            $detail->marketing_started->discount_price = $marketing_info[$goods_id]['marketing_started']['discount_price'];
            $detail->marketing_started->marketing_id = $marketing_info[$goods_id]['marketing_started']['marketing_id'];
            $detail->marketing_started->phase_id = $marketing_info[$goods_id]['marketing_started']['phase_id'];
            $phase = $marketing_info_ori['phase_started'];
            $detail->marketing_started->end_time_left = strtotime($phase['end_time']) - time();
        }

        if (!empty($marketing_info[$goods_id]['marketing_soon'])) {
            $detail->marketing_soon->user_limit = $marketing_info[$goods_id]['marketing_soon']['limited'];
            $detail->marketing_soon->sales_count = $marketing_info[$goods_id]['marketing_soon']['sales_count'];
            $detail->marketing_soon->marketing_stock = $marketing_info[$goods_id]['marketing_soon']['stock'];
            $detail->marketing_soon->discount_price = $marketing_info[$goods_id]['marketing_soon']['discount_price'];
            $detail->marketing_soon->marketing_id = $marketing_info[$goods_id]['marketing_soon']['marketing_id'];
            $detail->marketing_soon->phase_id = $marketing_info[$goods_id]['marketing_soon']['phase_id'];
            $phase = $marketing_info_ori['phase_soon'];
            $detail->marketing_soon->start_time_left = strtotime($phase['start_time']) - time();
        }
        GoodsService::insertGroupBookDetail($detail, 3);
        return Result::success($detail);
    }

    /**
     * 小程序 所有商品搜索
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCsGoodsListByName()
    {
        $this->validate(request(), [
            'keyword' => 'required'
        ],[
            'keyword.required' => '关键词不能为空',
        ]);
        $name = request('keyword');
        $data = CsGoodService::getListByName($name);

        return Result::success([
            'list' => $data->items(),
            'total' => $data->total(),
        ]);
    }
}
