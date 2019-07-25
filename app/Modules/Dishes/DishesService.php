<?php
namespace App\Modules\Dishes;

use App\BaseService;
use App\Support\MarketingApi;
use App\Modules\Merchant\Merchant;
use Illuminate\Database\Eloquent\Builder;


class DishesService extends BaseService
{
    /**
     * 获取单品指定分类的商品
     * @param $merchantId
     * @return DishesCategory[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getDishesCategory($merchantId)
    {
        $categorys =DishesCategory::whereHas('dishesGoods', function (Builder $query) {
            $query->where('status', DishesGoods::STATUS_ON);
        })
            ->where('merchant_id', $merchantId)
            ->where('status', 1)
            ->orderBy('sort', 'desc')
            ->get();

        if ($categorys) {
            $categorys->each(function ($item){
                $item->cat_type = 1;
            });

            $params['type'] = 2;
            $params['merchant_id'] = $merchantId;
            $params['marketing_id'] = 1;

            $marketing_info = MarketingApi::getMerchantGoodsListStart($params);
            if (!empty($marketing_info['list']['data'])) {
                $marketingObj = new DishesCategory();
                $marketingObj->id = 1;
                $marketingObj->name = '限时抢';
                $marketingObj->cat_type = 2;
                $categorys->prepend($marketingObj);
            }
        }

        return $categorys;
    }

    /**
     * 点菜添加
     * @param $merchantId
     * @param $userId
     * @param $dishesList
     * @return Dishes
     */
    public static function addDishes($merchantId,$userId,$dishesList)
    {
        $merchant = Merchant::findOrFail($merchantId);
        $dishes = new Dishes();
        $dishes->oper_id = $merchant->oper_id;
        $dishes->merchant_id = $merchant->id;
        $dishes->user_id = $userId;
        $dishes->save();

        foreach ($dishesList as $item){
            $dishesGoods = DishesGoods::findOrFail($item['id']);
            if ($dishesGoods['oper_id'] !== $merchant->oper_id){
                continue;
            }
            $dishesItem = new DishesItem();
            $dishesItem->oper_id = $merchant->oper_id;
            $dishesItem->merchant_id = $merchant->id;
            $dishesItem->dishes_id = $dishes->id;
            $dishesItem->user_id = $userId;
            $dishesItem->dishes_goods_id = $item['id'];
            $dishesItem->number = $item['number'];
            $dishesItem->dishes_goods_sale_price = $item['discount_price']??$dishesGoods['sale_price'];
            $dishesItem->ori_price = $dishesGoods['sale_price'];
            $dishesItem->dishes_goods_detail_image = $dishesGoods['detail_image'];
            $dishesItem->dishes_goods_name = $dishesGoods['name'];
            $dishesItem->marketing_id = $item['marketing_id']??0;
            $dishesItem->phase_id = $item['phase_id']??0;
            $dishesItem->dishes_goods_name = $dishesGoods['name'];
            $dishesItem->save();
        }

        return $dishes;
    }

    /**
     * 点菜的菜单详情
     * @param $dishesId
     * @return array
     */
    public static function detailDishes($dishesId)
    {
        $list = DishesItem::where('dishes_id',$dishesId)->get();
        $list->each(function($item){
            $item->total_price = $item->number * $item->dishes_goods_sale_price;

            $merchant_id = $item->merchant_id;
            $goods_id = $item->dishes_goods_id;
            $goods_type = 2;


            $marketing_info_ori = MarketingApi::addMarketingInfo($merchant_id,$goods_type,$goods_id);
            $marketing_info = $marketing_info_ori['list'];
            $item->marketing_started = (object) [];
            $item->marketing_soon = (object) [];

            if (!empty($marketing_info[$goods_id]['marketing_started'])) {
                $item->marketing_started->user_limit = $marketing_info[$goods_id]['marketing_started']['limited'];
                $item->marketing_started->sales_count = $marketing_info[$goods_id]['marketing_started']['sales_count'];
                $item->marketing_started->marketing_stock = $marketing_info[$goods_id]['marketing_started']['stock'];
                $item->marketing_started->discount_price = $marketing_info[$goods_id]['marketing_started']['discount_price'];
                $item->marketing_started->marketing_id = $marketing_info[$goods_id]['marketing_started']['marketing_id'];
                $item->marketing_started->phase_id = $marketing_info[$goods_id]['marketing_started']['phase_id'];
                $phase = $marketing_info_ori['phase_started'];
                $item->marketing_started->end_time_left = strtotime($phase['end_time']) - time();
            }

            if (!empty($marketing_info[$goods_id]['marketing_soon'])) {
                $item->marketing_soon->user_limit = $marketing_info[$goods_id]['marketing_soon']['limited'];
                $item->marketing_soon->sales_count = $marketing_info[$goods_id]['marketing_soon']['sales_count'];
                $item->marketing_soon->marketing_stock = $marketing_info[$goods_id]['marketing_soon']['stock'];
                $item->marketing_soon->discount_price = $marketing_info[$goods_id]['marketing_soon']['discount_price'];
                $item->marketing_soon->marketing_id = $marketing_info[$goods_id]['marketing_soon']['marketing_id'];
                $item->marketing_soon->phase_id = $marketing_info[$goods_id]['marketing_soon']['phase_id'];
                $phase = $marketing_info_ori['phase_soon'];
                $item->marketing_soon->start_time_left = strtotime($phase['start_time']) - time();
            }
        });

        return $list;
    }

    /**
     * 通过单品id获取订单单品列表
     * @param $dishes_id
     * @return DishesItem[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getDishesItemsByDishesId($dishes_id)
    {
        $dishesItems = DishesItem::where('dishes_id', $dishes_id)->get();

        return $dishesItems;
    }
}
