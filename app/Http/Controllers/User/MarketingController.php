<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/7
 * Time: 10:30
 */

namespace App\Http\Controllers\User;


use App\Exceptions\BaseResponseException;
use App\Support\MarketingApi;
use App\Modules\Cs\CsGood;
use App\Modules\Dishes\DishesGoods;
use App\Modules\Goods\Goods;
use App\Result;
use App\Support\Lbs;
use App\Support\Utils;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Expr\Cast\Object_;

class MarketingController
{

    public function getGoodsListStart()
    {

        $params['type'] = request('type');
        $params['city_id'] = request('city_id');
        $lng = request('lng');
        $lat = request('lat');
        $params['page'] = request('page');
        $params['pageSize'] = request('pageSize');
        $data = MarketingApi::getGoodsListStart($params['type'],$params['city_id'],$params['page'],$params['pageSize']);
        $requestData = $data;
        $phase_started = $data['phase_started'];
        $data = $data['list'];

        $list = collect();
        $total = 0;
        $marketing_id = 0;
        $phase_id = 0;
        $end_time_left = 0;
        $user_join = 0;
        if (!empty($data['data'])) {
            foreach ($data['data'] as $g) {
                if ($g['goods_type'] == 1) { //团购
                    $goods_info = Goods::find($g['goods_id']);
                } elseif ($g['goods_type'] == 2) { //点菜
                    $goods_info = DishesGoods::find($g['goods_id']);
                } elseif ($g['goods_type'] == 3) { //超市
                    $goods_info = CsGood::find($g['goods_id']);
                }
                if (empty($goods_info)) {
                    Log::error('活动商品信息错误' . $g['goods_type'] .'_' . $g['goods_id']);
                    continue;
                }
                $goods_info->marketing_started = (object) [];
                $goods_info->marketing_soon = (object) [];
                $goods_info->marketing_started->goods_type = $g['goods_type'];
                $goods_info->marketing_started->user_limit = $g['limited'];
                $goods_info->marketing_started->marketing_stock = $g['stock'];
                $goods_info->marketing_started->sales_count = $g['sales_count'];
                $goods_info->marketing_started->discount_price = $g['discount_price'];
                $goods_info->marketing_started->marketing_id = $g['marketing_id'];
                $goods_info->marketing_started->phase_id = $g['phase_id'];
                $marketing_id = $g['marketing_id'];
                $phase_id = $g['phase_id'];
                $list->push($goods_info);
            }
            $total = $data['total'];
        }

        if (!empty($phase_started)) {
            $end_time_left = strtotime($phase_started['end_time']) - time();
            $user_join = $phase_started['views_number'] + $phase_started['view_base'];
            $marketing_id = $phase_started['marketing_id'];
            $phase_id = $phase_started['id'];
        }

        //记录用户请求

        $current_device_no = request()->header('token');
        $rs = MarketingApi::userView($marketing_id,$phase_id,$current_device_no);
        if($lng && $lat && $params['type']==2){

            $list = $list->each(function ($item) use ($lng, $lat, $current_device_no) {
                $item->distance_ori = Lbs::getDistanceOfMerchant($item->merchant_id, $current_device_no, floatval($lng), floatval($lat));
                $item->distance = Utils::getFormativeDistance($item->distance_ori);
                return $item;
            })
                ->sortBy('distance_ori')->values()
            ;
        }
        return Result::success(['list'=>$list,'total'=>$total,'end_time_left'=>$end_time_left,'user_join'=>$user_join,'marketing_id'=>$marketing_id,'phase_id'=>$phase_id,'share_info'=>$requestData['share_info']]);

    }

    public function getGoodsListSoon()
    {
        $params['type'] = request('type');
        $params['city_id'] = request('city_id');
        $lng = request('lng');
        $lat = request('lat');
        $params['page'] = request('page');
        $params['pageSize'] = request('pageSize');
        $data = MarketingApi::getGoodsListSoon($params['type'],$params['city_id'],$params['page'],$params['pageSize']);
        $requestData = $data;
        $phase_soon = $data['phase_soon'];
        $data = $data['list'];
        $list = collect();
        $total = 0;
        $marketing_id = 0;
        $phase_id = 0;
        $start_time_left = 0;
        $user_view = 0;
        if (!empty($data['data'])) {
            foreach ($data['data'] as $g) {
                if ($g['goods_type'] == 1) { //团购
                    $goods_info = Goods::find($g['goods_id']);
                } elseif ($g['goods_type'] == 2) { //点菜
                    $goods_info = DishesGoods::find($g['goods_id']);
                } elseif ($g['goods_type'] == 3) { //超市
                    $goods_info = CsGood::find($g['goods_id']);
                }
                if (empty($goods_info)) {
                    Log::error('活动商品信息错误' . $g['goods_type'] .'_' . $g['goods_id']);
                    continue;
                }
                $goods_info->marketing_started = (object) [];
                $goods_info->marketing_soon = (object) [];
                $goods_info->marketing_soon->goods_type = $g['goods_type'];
                $goods_info->marketing_soon->user_limit = $g['limited'];
                $goods_info->marketing_soon->marketing_stock = $g['stock'];
                $goods_info->marketing_soon->sales_count = $g['sales_count'];
                $goods_info->marketing_soon->discount_price = $g['discount_price'];
                $goods_info->marketing_soon->marketing_id = $g['marketing_id'];
                $goods_info->marketing_soon->phase_id = $g['phase_id'];
                $list->push($goods_info);
            }
            $total = $data['total'];
        }

        if (!empty($phase_soon)) {
            $start_time_left = strtotime($phase_soon['start_time']) - time();
            $user_view = $phase_soon['views_number'] + $phase_soon['view_base'];
            $marketing_id = $phase_soon['marketing_id'];
            $phase_id = $phase_soon['id'];
        }

        //记录用户请求
        $current_device_no = request()->header('token');
        $rs = MarketingApi::userView($marketing_id,$phase_id,$current_device_no);
        if($lng && $lat && $params['type']==2){

            $list = $list->each(function ($item) use ($lng, $lat, $current_device_no) {
                $item->distance_ori = Lbs::getDistanceOfMerchant($item->merchant_id, $current_device_no, floatval($lng), floatval($lat));
                $item->distance = Utils::getFormativeDistance($item->distance_ori);
                return $item;
            })
                ->sortBy('distance_ori')->values()
            ;
        }
        return Result::success(['list'=>$list,'total'=>$total,'start_time_left'=>$start_time_left,'user_view'=>$user_view,'marketing_id'=>$marketing_id,'phase_id'=>$phase_id,'share_info'=>$requestData['share_info']]);
    }

    public function getMerchantGoodsListStart()
    {
        $userId = request()->get('current_user')->id??0;
        $merchant_id = request('merchant_id');
        $marketing_id = request('marketing_id');
        $params['type'] = request('type');
        $params['page'] = request('page');
        $params['pageSize'] = request('pageSize');
        $params['merchant_id'] = $merchant_id;
        $params['marketing_id'] = $marketing_id;
        $params['user_id'] = $userId;
        if (empty($params['type'])) {
            $params['type'] = $merchant_id<1000000000?2:3;
        }
        $data = MarketingApi::getMerchantGoodsListStart($params);

        $list = collect();
        $total = 0;
        $marketing_id = 0;
        $phase_id = 0;
        if (isset($data['list'])&&!empty($data['list']['data'])) {
            $data = $data['list'];
            foreach ($data['data'] as $g) {
                if ($g['goods_type'] == 1) { //团购
                    $goods_info = Goods::find($g['goods_id']);
                    if ($goods_info && $goods_info->status == Goods::STATUS_OFF) {
                        continue;
                    }
                } elseif ($g['goods_type'] == 2) { //点菜
                    $goods_info = DishesGoods::find($g['goods_id']);
                    if ($goods_info && $goods_info->status == DishesGoods::STATUS_OFF) {
                        continue;
                    }
                } elseif ($g['goods_type'] == 3) { //超市
                    $goods_info = CsGood::find($g['goods_id']);
                    if ($goods_info && $goods_info->status == CsGood::STATUS_OFF) {
                        continue;
                    }
                }
                if (empty($goods_info)) {
                    Log::error('活动商品信息错误' . $g['goods_type'] .'_' . $g['goods_id']);
                    continue;
                }
                $goods_info->marketing_started = (object) [];
                $goods_info->marketing_soon = (object) [];
                $goods_info->marketing_started->goods_type = $g['goods_type'];
                $goods_info->marketing_started->user_limit = $g['limited'];
                $goods_info->marketing_started->marketing_stock = $g['stock'];
                $goods_info->marketing_started->sales_count = $g['sales_count'];
                $goods_info->marketing_started->discount_price = $g['discount_price'];
                $goods_info->marketing_started->marketing_id = $g['marketing_id'];
                $goods_info->marketing_started->phase_id = $g['phase_id'];
                $marketing_id = $g['marketing_id'];
                $phase_id = $g['phase_id'];
                $list->push($goods_info);
            }
            $total = $data['total'];
        }

        return Result::success(['list'=>$list,'total'=>$total,'marketing_id'=>$marketing_id,'phase_id'=>$phase_id,'user_id'=>$userId]);
    }

    public function userView()
    {
        $marketing_id = request('marketing_id');
        $phase_id = request('phase_id');
        $current_device_no = request()->header('token');

        $rs = MarketingApi::userView($marketing_id,$phase_id,$current_device_no);

        return Result::success(['list'=>$rs]);
    }

    public function getMarketingInfo()
    {

        $marketing_id = request('marketing_id',1);

        $rs = MarketingApi::getMarketingInfo($marketing_id);
        $rt = (object) $rs;

        $rt->marketing = (object) $rt->marketing;
        $rt->phase_started = (object) $rt->phase_started;
        $rt->phase_soon = (object) $rt->phase_soon;

        return Result::success($rt);
    }
}
