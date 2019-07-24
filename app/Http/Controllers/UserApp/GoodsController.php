<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/13
 * Time: 13:33
 */

namespace App\Http\Controllers\UserApp;


use App\Http\Controllers\Controller;
use App\Support\MarketingApi;
use App\Modules\Goods\GoodsService;
use App\Modules\Goods\Goods;
use App\Modules\Merchant\Merchant;
use App\Result;




class GoodsController extends Controller
{

    public function getList()
    {
        $this->validate(request(), [
            'merchant_id' => 'required|integer|min:1',
        ]);
        $merchant_id = request('merchant_id');
        $list = GoodsService::userGoodsList($merchant_id);
        return Result::success(['list' => $list]);
    }

    public function detail()
    {
        $this->validate(request(), [
            'id' => 'required|integer|min:1',
        ]);
        $goods_id = request('id');
        $detail = Goods::withTrashed()->where('id', request('id'))->firstOrFail();
        $detail->pic_list_new = $detail->pic_list;
        $detail->pic_list_arr_new = $detail->pic_list ? explode(',', $detail->pic_list) : [];
        $detail->pic_list = $detail->pic_list ? explode(',', $detail->pic_list) : [];
        $merchant = Merchant::findOrFail($detail->merchant_id);
        $detail->business_time = json_decode($merchant->business_time, 1);

        $merchant_id = $detail->merchant_id;
        $goods_type = 1;


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
        //添加拼团数据
        GoodsService::insertGroupBookDetail($detail, 1);
        return Result::success($detail);
    }
}
