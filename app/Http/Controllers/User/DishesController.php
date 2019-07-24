<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/5/27
 * Time: 16:46
 */

namespace App\Http\Controllers\User;

use App\Exceptions\BaseResponseException;
use App\Exceptions\DataNotFoundException;
use App\Exceptions\ParamInvalidException;
use App\Http\Controllers\Controller;
use App\Support\MarketingApi;
use App\Modules\Dishes\DishesGoods;
use App\Modules\Dishes\DishesGoodsService;
use App\Modules\Dishes\DishesService;
use App\Modules\Merchant\MerchantSettingService;
use App\Modules\Order\OrderService;
use App\Result;
use App\ResultCode;


class DishesController extends Controller
{
    /**
     * 判断单品购买功能是否开启
     * MerchantDishesController constructor.
     */
    public function __construct()
    {
        $merchantId = request('merchant_id');
        if (!$merchantId){
            throw new BaseResponseException('商户ID不能为空');
        }
        $result = MerchantSettingService::getValueByKey($merchantId, 'dishes_enabled');
        if (!$result){
            throw new BaseResponseException('单品购买功能尚未开启！');
        }
    }

    /**
     * 获取单品分类
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getDishesCategory()
    {
        $merchantId = request('merchant_id');
        $categorys = DishesService::getDishesCategory($merchantId);
        return Result::success([
            'list' => $categorys
        ]);
    }


    /**
     * 获取热门菜品
     *
     */
    public function getHotDishesGoods()
    {

        $merchantId = request('merchant_id');
        $hotDishesGoods = DishesGoodsService::getHotDishesGoods($merchantId);
        return Result::success([
            'list' => $hotDishesGoods
        ]);
    }


    /**
     * 获取单品指定分类的商品
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getDishesGoods()
    {
        $merchantId = request('merchant_id');
        $categoryId = request('category_id');
        if (!$categoryId){
            throw new BaseResponseException('分类ID不能为空');
        }

        $list = DishesGoodsService::getDishesGoods($merchantId,$categoryId);

         return Result::success([
             'list' => $list,
         ]);

    }

    public function getDishesGood()
    {
        $goodId = request('id');
        if (!$goodId){
            throw new BaseResponseException('ID不能为空');
        }
        $good = DishesGoodsService::getByIdAndMerchantId($goodId,request('merchant_id'));
        if(!$good||$good->status!=DishesGoods::STATUS_ON) throw new DataNotFoundException('该商品或已下架');
        return Result::success([
            'data'  =>  $good
        ]);
    }


   //点菜操作

    public function add()
    {
        $userId = request()->get('current_user')->id??0;
        $this->validate(request(), [
            'merchant_id' => 'required|integer|min:1',
        ]);
        $dishesList = request('goods_list');
        if(is_string($dishesList)){
            $dishesList = json_decode($dishesList,true);
        }

        $merchantId = request('merchant_id');
        if (empty($dishesList)){
            throw new ParamInvalidException('单品列表为空');
        }
        if(sizeof($dishesList) < 1){
            throw new ParamInvalidException('参数不合法1');
        }

        $marketing_goods_ids = [];
        $marketing_id = 0;
        $phase_id = 0;
        foreach ($dishesList as $item) {
            if (!isset($item['id']) || !isset($item['number']) || $item['number']<=0) {
                throw new ParamInvalidException('参数不合法2');
            }
            $dishesGoods = DishesGoods::findOrFail($item['id']);
            if ($dishesGoods->status == DishesGoods::STATUS_OFF) {
                throw new BaseResponseException('菜单已变更, 请刷新页面');
            }

            if ($dishesGoods->stock< $item['number']) {
                throw new BaseResponseException('商品'.$dishesGoods->name.'库存不足',ResultCode::STOCK_EMPTY);
            }

            if (!empty($item['marketing_id']) && !empty($item['phase_id'])) {
                $marketing_goods_ids[] = $item['id'];
                $marketing_id = $item['marketing_id'];
                $phase_id = $item['phase_id'];
            }
        }

        if (!empty($marketing_goods_ids)) {

            $marketing_goods_limit = MarketingApi::getCanDiscountGoodsFromMarketing($merchantId,$marketing_goods_ids,$phase_id,$marketing_id,2,$userId);
            if (empty($marketing_goods_limit['list'])) {
                throw new BaseResponseException('当前活动已结束，请重新选购',ResultCode::MARKETING_GOODS_EXPIRED);
            }

            $marketing_goods_limit = $marketing_goods_limit['list'];

            foreach ($dishesList as &$item) {

                if (!empty($item['marketing_id']) && !empty($item['phase_id'])) {
                    if (!empty($marketing_goods_limit[$item['id']])) {
                        $marketing_goods_info = $marketing_goods_limit[$item['id']];
                        if ($marketing_goods_info['limited']< $item['number']) {
                            throw new BaseResponseException('抢购商品达到数量限制1',ResultCode::MARKETING_OVER_USER_LIMIT);
                        }
                        $item['discount_price'] = $marketing_goods_info['discount_price'];
                    }else {
                        throw new BaseResponseException('抢购商品达到数量限制2',ResultCode::MARKETING_OVER_USER_LIMIT);
                    }
                }


            }
        }
        $dishes = DishesService::addDishes($merchantId,$userId,$dishesList);

        return Result::success($dishes);
    }

    /**
     * 点菜的菜单详情
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function detail()
    {
        $this->validate(request(), [
            'dishes_id' => 'required|integer|min:1',
        ]);

        $dishesId = request('dishes_id');
        $detailDishes = DishesService::detailDishes($dishesId);

        return Result::success($detailDishes);
    }


    /**
     * 点菜商品详情
     */
    public function goodsDetail()
    {
        $this->validate(request(), [
            'id' => 'required|integer|min:1',
        ]);
        $goods_id = request('id');
        $detail = DishesGoods::withTrashed()->where('id', request('id'))->firstOrFail();
        //$detail->detail_image = $detail->detail_image ? explode(',', $detail->detail_image) : [];
        $merchant_id = $detail->merchant_id;
        $goods_type = 2;


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
        return Result::success($detail);
    }


}
