<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/9/5
 * Time: 下午3:01
 */

namespace App\Http\Controllers\UserApp;

use App\Exceptions\BaseResponseException;
use App\Exceptions\ParamInvalidException;
use App\Http\Controllers\Controller;
use App\Support\MarketingApi;
use App\Modules\Cs\CsGood;
use App\Modules\Dishes\DishesGoods;
use App\Modules\Dishes\DishesGoodsService;
use App\Modules\Dishes\DishesService;
use App\Modules\Goods\Goods;
use App\Modules\Merchant\MerchantSettingService;
use App\Modules\Order\OrderService;
use App\Result;
use App\ResultCode;
use Illuminate\Support\Facades\Log;


class DishesController extends Controller
{
    /**
     * 判断单品购买功能是否开启
     * MerchantDishesController constructor.
     */
    public function __construct()
    {
        $merchantId = request('merchant_id');
        if (!$merchantId) {
            throw new BaseResponseException('商户ID不能为空');
        }
        $result = MerchantSettingService::getValueByKey($merchantId, 'dishes_enabled');
        if (!$result) {
            throw new BaseResponseException('单品购买功能尚未开启！');
        }
    }

    /**
     * 获取单品分类
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getDishesCategory()
    {
        //获取分类列表
        $merchantId = request('merchant_id');
        $list = DishesService::getDishesCategory($merchantId);

        foreach ($list as $category) {
            $categoryId = $category['id'];
            if ($categoryId) {
                //获取菜品列表
                if (!empty($category['cat_type']) && $category['cat_type']== 2) {
                    $params['page'] = 1;
                    $params['type'] = 2;
                    $params['pageSize'] = 100;
                    $params['merchant_id'] = $merchantId;
                    $params['marketing_id'] = $categoryId;
                    $data = MarketingApi::getMerchantGoodsListStart($params);
                    $data = $data['list'];
                    $subList = collect();
                    $total = 0;
                    $marketing_id = 0;
                    $phase_id = 0;
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
                                throw new BaseResponseException('活动商品信息错误');
                            }

                            $marketing_info_ori = MarketingApi::addMarketingInfo($merchantId,OrderService::MARKETING_GOODS_TYPE_DISHED,$goods_info->id);
                            $marketing_info = $marketing_info_ori['list'];
                            $goods_info->marketing_started = (object) [];
                            $goods_info->marketing_soon = (object) [];

                            if (!empty($marketing_info[$goods_info->id]['marketing_started'])) {
                                $goods_info->marketing_started->user_limit = $marketing_info[$goods_info->id]['marketing_started']['limited'];
                                $goods_info->marketing_started->sales_count = $marketing_info[$goods_info->id]['marketing_started']['sales_count'];
                                $goods_info->marketing_started->marketing_stock = $marketing_info[$goods_info->id]['marketing_started']['stock'];
                                $goods_info->marketing_started->discount_price = $marketing_info[$goods_info->id]['marketing_started']['discount_price'];
                                $goods_info->marketing_started->marketing_id = $marketing_info[$goods_info->id]['marketing_started']['marketing_id'];
                                $goods_info->marketing_started->phase_id = $marketing_info[$goods_info->id]['marketing_started']['phase_id'];
                                $phase = $marketing_info_ori['phase_started'];
                                $goods_info->marketing_started->end_time_left = strtotime($phase['end_time']) - time();
                            }

                            if (!empty($marketing_info[$goods_info->id]['marketing_soon'])) {
                                $goods_info->marketing_soon->user_limit = $marketing_info[$goods_info->id]['marketing_soon']['limited'];
                                $goods_info->marketing_soon->sales_count = $marketing_info[$goods_info->id]['marketing_soon']['sales_count'];
                                $goods_info->marketing_soon->marketing_stock = $marketing_info[$goods_info->id]['marketing_soon']['stock'];
                                $goods_info->marketing_soon->discount_price = $marketing_info[$goods_info->id]['marketing_soon']['discount_price'];
                                $goods_info->marketing_soon->marketing_id = $marketing_info[$goods_info->id]['marketing_soon']['marketing_id'];
                                $goods_info->marketing_soon->phase_id = $marketing_info[$goods_info->id]['marketing_soon']['phase_id'];
                                $phase = $marketing_info_ori['phase_soon'];
                                $goods_info->marketing_soon->start_time_left = strtotime($phase['start_time']) - time();
                            }

                            $marketing_id = $g['marketing_id'];
                            $phase_id = $g['phase_id'];
                            $subList->push($goods_info);
                        }
                        $total = $data['total'];
                    }

                } else {
                    $subList = DishesGoodsService::getDishesGoods($merchantId, $categoryId);
                }

                $category['subList'] = $subList;
            } else {
                $category['subList'] = array();
            }

        }
        return Result::success([
            'list' => $list
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
        if (!$categoryId) {
            throw new BaseResponseException('分类ID不能为空');
        }

        $list = DishesGoodsService::getDishesGoods($merchantId, $categoryId);

        return Result::success([
            'list' => $list,
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
        if (is_string($dishesList)) {
            $dishesList = json_decode($dishesList, true);
        }
        Log::info('disesList11',['diesList' => $dishesList]);

        $merchantId = request('merchant_id');
        if (empty($dishesList)) {
            throw new ParamInvalidException('单品列表为空');
        }
        if (sizeof($dishesList) < 1) {
            throw new ParamInvalidException('参数不合法1');
        }



        $marketing_goods_ids = [];
        $marketing_id = 0;
        $phase_id = 0;
        foreach ($dishesList as $item) {
            if (!isset($item['id']) || !isset($item['number'])|| $item['number']<=0) {
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

        $dishes = DishesService::addDishes($merchantId, $userId, $dishesList);

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
        $list = DishesService::detailDishes($dishesId);

        return Result::success(['list' => $list]);
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
        $goods_type = OrderService::MARKETING_GOODS_TYPE_DISHED;


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
