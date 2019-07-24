<?php

namespace App\Modules\CsOrder;

use App\BaseService;
use App\Modules\Cs\CsGood;
use App\Modules\Order\Order;
use App\Modules\Order\OrderService;

class CsOrderGoodService extends BaseService
{


    /**
     * 订单取消增加库存 减销量
     * @param $order_id
     * @return bool
     */
    public static function orderCancel($order_id)
    {

        $list = CsOrderGood::where('order_id',$order_id)->get();
        if (empty($list)) {
            return false;
        }

        foreach ($list as $item) {
            $good = CsGood::find($item->cs_goods_id);
            if (!$good) {
                continue;
            }
            $good->sale_num -= $item->number;
            //不能为复数
            if ($good->sale_num<0) {
                $good->sale_num = 0;
            }
            $good->stock += $item->number;
            $good->save();
        }

        return true;
    }


    /**
     * 获取活动商品列表
     * @param $orderId
     * @param $marketingId
     * @param $phaseId
     * @return array
     */
    public static function getPhaseGoodsList($orderId,$marketingId,$phaseId)
    {
        $returnList = [];
        $goodsList = CsOrderGood::where('order_id',$orderId)
                        ->where('marketing_id',$marketingId)
                        ->where('phase_id',$phaseId)
                        ->get();
        if(!empty($goodsList)){
            foreach ($goodsList as $good){
                $returnList[] = [
                    'goods_id'  =>  $good->cs_goods_id,
                    'price'     =>  $good->original_price,
                    'discount_price'=>$good->price,
                    'buy_number'=>  $good->number,
                    'total_price'=> $good->price*$good->number,
                    'goods_type'=>  OrderService::MARKETING_GOODS_TYPE_SUPERMARKET,
                ];
            }
        }
        return $returnList;
    }
}
