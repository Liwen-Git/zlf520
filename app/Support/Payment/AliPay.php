<?php

namespace App\Support\Payment;
use App\Exceptions\BaseResponseException;
use App\Modules\Ali\AliService;
use App\Modules\Order\Order;
use App\Modules\Order\OrderPay;
use App\Modules\Order\OrderService;
use App\Modules\User\User;
use Illuminate\Support\Facades\Log;
use Yansongda\Pay\Pay;

class AliPay extends PayBase{

    public function setConfig($config)
    {
        $this->_configs = $config;
        return $this;
    }

    public function getConfig()
    {
        if(!$this->_configs){
            $this->_configs =AliService::getAliPayApp();
        }
        return $this->_configs;
    }

    public function getAliPayInstance()
    {
        return Pay::alipay($this->getConfig());
    }

    public function buy(User $user, Order $order)
    {
    }

    /**
     * 处理异步通知
     */
    public function doNotify()
    {
        $aliContent = request()->all();
        Log::info('支付宝回调', ['aliContent' => $aliContent]);
        if($aliContent['trade_status']=='TRADE_SUCCESS'){
            OrderService::paySuccess($aliContent['out_trade_no'], $aliContent['trade_no'], $aliContent['total_amount'], Order::PAY_TYPE_ALIPAY, $aliContent['gmt_payment']);
            $this->getAliPayInstance()->success();
        } else {
            throw new BaseResponseException('支付宝回调异常');
        }

    }

    public function refund(Order $order)
    {
        if(!in_array($order->status,[Order::STATUS_PAID, Order::STATUS_UNDELIVERED, Order::STATUS_PAID_UNSETTLED, Order::STATUS_REFUND_FAIL])){
            throw new BaseResponseException('订单状态不允许退款');
        }
        if ($order->pay_type != Order::PAY_TYPE_ALIPAY) {
            throw new BaseResponseException('不是支付宝支付的订单');
        }
        // 查询支付记录
        $orderPay = OrderPay::where('order_id', $order->id)->firstOrFail();
        return OrderService::refund($orderPay,$order,$this);
    }

    public function handleRefund($order,$orderPay,$orderRefund){
        $data = [
            'out_trade_no'  =>  $order->order_no,
            'refund_amount' =>  $orderPay->amount,
            'out_request_no'=>  $orderRefund->refund_no,
        ];
        $result = $this->getAliPayInstance()->refund($data);
        return ($result->msg=='Success' && $result->code==10000) ? $result : false;
    }

    public function getApp($order)
    {
        return $this->getAliPayInstance()->app($order)->getContent();
    }
}