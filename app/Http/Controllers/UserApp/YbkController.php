<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/20
 * Time: 8:57
 */
namespace App\Http\Controllers\UserApp;

use App\Exceptions\BaseResponseException;
use App\Http\Controllers\Controller;
use App\Modules\CsOrder\CsOrderGood;
use App\Modules\Dishes\DishesItem;
use App\Modules\Goods\Goods;
use App\Modules\Order\Order;
use App\Modules\Order\OrderItem;
use App\Modules\Ybk\YbkAnchorService;
use App\Result;

class YbkController extends Controller
{
    /**
     * 搜索云博客主播信息
     */
    public function searchAnchor()
    {
        $keyword = request('keyword');
        if ($keyword != 0 && !is_numeric($keyword)) {
            throw new BaseResponseException('参数错误');
        }

        $list = YbkAnchorService::search($keyword);

        return Result::success($list);

    }

    public function getList()
    {

        $params['pageSize'] = request('pageSize',15);

        $data = YbkAnchorService::getList($params);

        return Result::success([
            'list' => $data->items(),
            'total' => $data->total(),
        ]);
    }

    public function orderDetail()
    {
        $this->validate(request(), [
            'order_no' => 'required'
        ]);

        $detail = Order::where('order_no', request('order_no'))->firstOrFail();
        // 只返回一个核销码
        $orderItem = OrderItem::where('order_id', $detail->id)->first();
        $detail->items = !empty($orderItem) ? [$orderItem] : [];

        // 单品订单
        if ($detail->type == Order::TYPE_DISHES) {
            $detail->dishes_items = DishesItem::where('dishes_id', $detail->dishes_id)->get();
            $detail->order_goods_number = DishesItem::where('dishes_id',$detail->dishes_id)->sum('number');
        }else if($detail->type == Order::TYPE_GROUP_BUY){
            $detail->goods_end_date = Goods::withTrashed()->where('id', $detail->goods_id)->value('end_date');
        }else if($detail->type == Order::TYPE_SUPERMARKET){
            $detail->order_goods_number = CsOrderGood::where('order_id',$detail->id)->sum('number');
            $detail->order_goods = CsOrderGood::where('order_id',$detail->id)->with('cs_goods:id,logo')->get();
        }

        $detail->ybk_anchor_nickname = '';
        if (!empty($detail->ybk_anchor_id)) {
            $anchor = YbkAnchorService::getByNumber($detail->ybk_anchor_id);
            $detail->ybk_anchor_nickname = $anchor->nickname;
        }
        return Result::success($detail);
    }
}