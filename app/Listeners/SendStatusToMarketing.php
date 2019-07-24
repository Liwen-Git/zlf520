<?php

namespace App\Listeners;

use App\Events\OrdersUpdatedEvent;
use App\Modules\Order\Order;
use App\Modules\Order\OrderService;
use App\Support\MarketingApi;

/**
 * 通知营销端监听者
 * Class SendStatusToMarketing
 * @package App\Listeners
 */
class SendStatusToMarketing
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  OrdersUpdatedEvent  $event
     * @return void
     */
    public function handle(OrdersUpdatedEvent $event)
    {
        $order = $event->order;
        $marketingGoodsType = [
            Order::TYPE_GROUP_BUY,
            Order::TYPE_DISHES,
            Order::TYPE_SUPERMARKET,
        ];
        $isNeedInform = (in_array($order->type,$marketingGoodsType) && $order->marketing_id!=0);
        // 睡眠用于等待下单商品入库成功;
        if($isNeedInform) {
            unset($order->express_address);
            MarketingApi::informRecordOrderToMarketing($order);
        }

        MarketingApi::informChangeOrderStatusToMarketing($order->id, $order->status);
    }
}
