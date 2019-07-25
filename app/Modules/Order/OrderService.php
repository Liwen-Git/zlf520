<?php
/**
 * Created by PhpStorm.
 * User: 57458
 * Date: 2018/7/25
 * Time: 16:16
 */

namespace App\Modules\Order;


use App\BaseService;
use App\DataCacheService;
use App\Exceptions\BaseResponseException;
use App\Exceptions\DataNotFoundException;
use App\Exceptions\ParamInvalidException;
use App\Http\Controllers\UserApp\GroupBookController;
use App\Jobs\Cs\DeliveredOrderAutoFinishedJob;
use App\Jobs\OrderPaidJob;
use App\Jobs\XingePushOrderInfoJob;
use App\Modules\Cs\CsGood;
use App\Modules\Cs\CsMerchant;
use App\Modules\Cs\CsMerchantService;
use App\Modules\Cs\CsMerchantSettingService;
use App\Modules\CsOrder\CsOrderGood;
use App\Modules\CsOrder\CsOrderGoodService;
use App\Modules\CsStatistics\CsStatisticsMerchantOrderService;
use App\Modules\Dishes\DishesGoods;
use App\Jobs\OrderFinishedJob;
use App\Modules\Dishes\DishesGoodsService;
use App\Modules\Dishes\DishesItem;
use App\Modules\Goods\Goods;
use App\Modules\Goods\GoodsService;
use App\Modules\Invite\InviteChannel;
use App\Modules\Invite\InviteChannelService;
use App\Modules\Invite\InviteUserRecord;
use App\Modules\Invite\InviteUserService;
use App\Modules\Merchant\Merchant;
use App\Modules\Merchant\MerchantService;
use App\Modules\Payment\Payment;
use App\Modules\Platform\PlatformTradeRecord;
use App\Modules\Setting\SettingService;
use App\Modules\Sms\SmsVerifyCodeService;
use App\Modules\User\User;
use App\Modules\Sms\SmsService;
use App\Modules\Oper\Oper;
use App\Modules\UserCredit\UserCreditRecord;
use App\Result;
use App\ResultCode;
use App\Support\Curl;
use App\Support\Lbs;
use App\Support\Payment\AliPay;
use App\Support\Payment\WalletPay;
use App\Support\Payment\WechatPay;
use App\Support\Utils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\MarketingApi;

class OrderService extends BaseService
{
    const MARKETING_GOODS_TYPE_GROUP = 1;           // 团购商品
    const MARKETING_GOODS_TYPE_DISHED= 2;           // 点餐商品
    const MARKETING_GOODS_TYPE_SUPERMARKET =3;      // 超市商品


    /**
     * 查询订单列表
     * @param array $params
     * @param bool $getWithQuery
     * @return Order|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getList(array $params, $getWithQuery = false)
    {
        $operId = array_get($params, 'operId');
        $userId = array_get($params, 'userId');
        $bizerId = array_get($params, 'bizerId');
        $merchantId = array_get($params, 'merchantId');
        $orderNo = array_get($params, 'orderNo');
        $notifyMobile = array_get($params, 'notifyMobile');
        $keyword = array_get($params, 'keyword');
        $type = array_get($params, 'type');
        $merchantType = array_get($params, 'merchantType', Order::MERCHANT_TYPE_NORMAL);
        $status = array_get($params, 'status');
        $goodsName = array_get($params, 'goodsName');
        $startCreatedAt = array_get($params, 'startCreatedAt');
        $endCreatedAt = array_get($params, 'endCreatedAt');
        $startPayTime = array_get($params, 'startPayTime');
        $endPayTime = array_get($params, 'endPayTime');
        $startFinishTime = array_get($params, 'startFinishTime');
        $endFinishTime = array_get($params, 'endFinishTime');
        $reminder = array_get($params, 'reminder', null);
        $settlementId = array_get($params, 'settlement_id');
        $marketingId = array_get($params, 'marketingId');

        $query = Order::where(function (Builder $query) {
            $query->where('type', Order::TYPE_GROUP_BUY)
                ->orWhere(function (Builder $query) {
                    $query->where('type', Order::TYPE_SCAN_QRCODE_PAY)
                        ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_REFUNDED, Order::STATUS_FINISHED]);
                })->orWhere(function (Builder $query) {
                    $query->where('type', Order::TYPE_DISHES);
                })->orWhere(function (Builder $query) {
                    $query->where('merchant_type', Order::MERCHANT_TYPE_SUPERMARKET)
                        ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_REFUNDED, Order::STATUS_FINISHED, Order::STATUS_UNDELIVERED, Order::STATUS_NOT_TAKE_BY_SELF, Order::STATUS_DELIVERED, Order::STATUS_PAID_UNSETTLED, Order::STATUS_REFUND_FAIL]);
                });
        });

        if ($merchantType == Order::MERCHANT_TYPE_SUPERMARKET) {
            $query->with('csOrderGoods');
        }

        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        if(!empty($merchantId)){
            if (is_array($merchantId) || $merchantId instanceof Collection) {
                $query->whereIn('merchant_id', $merchantId);
            } else {
                $query->where('merchant_id', $merchantId);
            }
        }
        if($settlementId>0){
            $query->where('settlement_id', $settlementId);
        }
        if ($operId > 0) {
            $query->where('oper_id', $operId);
        }
        if ($bizerId > 0) {
            $query->where('bizer_id', $bizerId);
        }
        if ($merchantType) {
            $query->where('merchant_type', $merchantType);
        }
        if ($orderNo) {
            $query->where('order_no', 'like', "%$orderNo%");
        }
        if ($notifyMobile) {
            $query->where('notify_mobile', 'like', "%$notifyMobile%");
        }
        if ($startCreatedAt && $endCreatedAt) {
            $query->whereBetween('created_at', [$startCreatedAt, $endCreatedAt]);
        } else if ($startCreatedAt) {
            $query->where('created_at', '>', $startCreatedAt);
        } else if ($endCreatedAt) {
            $query->where('created_at', '<', $endCreatedAt);
        }
        if ($startPayTime && $endPayTime) {
            $query->whereBetween('pay_time', [$startPayTime, $endPayTime]);
        } else if ($startPayTime) {
            $query->where('pay_time', '>', $startPayTime);
        } else if ($endPayTime) {
            $query->where('pay_time', '<', $endPayTime);
        }

        if ($startFinishTime && $endFinishTime) {
            $query->whereBetween('finish_time', [$startFinishTime, $endFinishTime]);
        } else if ($startFinishTime) {
            $query->where('finish_time', '>', $startFinishTime);
        } else if ($endFinishTime) {
            $query->where('finish_time', '<', $endFinishTime);
        }

        if ($type) {
            if (is_array($type)) {
                $query->whereIn('type', $type);
            } else {
                $query->where('type', $type);
            }
        }

        if ($status) {
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        } elseif (!empty($params['from_saas'])) {
            //saas只看 已支付、已完成、已退款的
            $query->whereIn('status', [Order::STATUS_PAID,Order::STATUS_REFUNDING,Order::STATUS_REFUNDED,Order::STATUS_FINISHED,Order::STATUS_UNDELIVERED,Order::STATUS_NOT_TAKE_BY_SELF,Order::STATUS_DELIVERED,Order::STATUS_PAID_UNSETTLED,Order::STATUS_REFUND_FAIL]);
        }

        if ($marketingId !== '' && $marketingId !== null) {
            $query->where('marketing_id', $marketingId);
        }

        if (!empty($params['platform_only'])) {
            $query->where('pay_target_type',2);
        }

        if($goodsName){
            $query->where('goods_name', 'like', "%$goodsName%");
        }

        if ($keyword) {
            $query->where(function (Builder $query) use ($keyword) {
                $query->where('order_no', 'like', "%$keyword%")
                    ->orWhere('notify_mobile', 'like', "%$keyword%");
            });
        }

        if ($reminder) {
            $query->where('reminder_time', '<>', null);
        }

        $query->with('oper:id,name');
        $query->with('user:id,name,avatar_url,wx_nick_name,wx_avatar_url');
        $query->with('ybkAnchor:id,ybk_pk,nickname');
        $query->orderBy('id', 'desc');

        if ($getWithQuery) {
            return $query;
        }

        $data = $query->paginate();

        $merchantIds = $data->pluck('merchant_id');
        if ($merchantType == Order::MERCHANT_TYPE_NORMAL) {
            $merchants = Merchant::whereIn('id', $merchantIds->all())->get(['id', 'name'])->keyBy('id');
        } elseif ($merchantType == Order::MERCHANT_TYPE_SUPERMARKET) {
            $merchants = CsMerchant::whereIn('id', $merchantIds->all())->get(['id', 'name'])->keyBy('id');
        } else {
            throw new BaseResponseException('merchant_type 订单类型错误');
        }

        foreach ($data as $key => $item) {
            //$item->makeHidden('deliver_code');
            $item->merchant_name = isset($merchants[$item->merchant_id]) ? $merchants[$item->merchant_id]->name : $item->merchant_name;
            $item->operName = Oper::where('id', $item->oper_id > 0 ? $item->oper_id : $item->audit_oper_id)->value('name');
            $item->operId = $item->oper_id > 0 ? $item->oper_id : $item->audit_oper_id;

            if ($item->type == Order::TYPE_DISHES){
                $dishesItems = DishesItem::where('dishes_id', $item->dishes_id)->get();
                $data[$key]['dishes_items'] = $dishesItems;
            }
            $item->items = OrderItem::where('order_id', $item->id)->get();

            $item->goods_end_date = Goods::where('id', $item->goods_id)->value('end_date');
            $item->pay_type_name = Order::getPayTypeText($item->pay_type);

            if ($merchantType == Order::MERCHANT_TYPE_SUPERMARKET) {
                if (in_array($item->status, [Order::STATUS_UNDELIVERED, Order::STATUS_NOT_TAKE_BY_SELF, Order::STATUS_DELIVERED])) {
                    $item->take_time = self::formatTime(time(), strtotime($item->pay_time));
                } elseif ($item->status == Order::STATUS_FINISHED) {
                    $item->take_time = self::formatTime(strtotime($item->finish_time), strtotime($item->pay_time));
                } elseif ($item->status == Order::STATUS_REFUNDED) {
                    $item->take_time = self::formatTime(strtotime($item->refund_time), strtotime($item->pay_time));
                }
                $item->express_address = json_decode($item->express_address, true);
            }
        }

        return $data;
    }

    /**
     * 获取支付到运营中心的结算单的订单列表
     * @param $settlementId
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getListByOperSettlementId($settlementId)
    {
        return self::getListBySettlementId($settlementId, Order::PAY_TARGET_TYPE_OPER);
    }

    /**
     * 获取支付到平台的结算单的订单列表
     * @param $settlementId
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getListByPlatformSettlementId($settlementId)
    {
        return self::getListBySettlementId($settlementId, Order::PAY_TARGET_TYPE_PLATFORM);
    }

    /**
     * 根据结算ID与支付目标类型获取订单列表
     * @param $settlementId int 结算ID
     * @param $payTargetType int 支付目标类型 1-支付给运营中心 2-支付给平台
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getListBySettlementId($settlementId, $payTargetType)
    {
        $data = Order::where('settlement_id', $settlementId)
            ->where('pay_target_type', $payTargetType)
            ->with('merchant:id,name')
            ->with('oper:id,name')
            ->orderBy('id', 'desc')
            ->paginate();
        return $data;
    }

    /**
     * 核销订单
     * @param $merchantId
     * @param $verifyCode
     * @return Order
     */
    public static function verifyOrder($merchantId, $verifyCode)
    {
        $order_id = OrderItem::where('verify_code', $verifyCode)
            ->where('merchant_id', $merchantId)
            ->value('order_id');

        if (!$order_id) {
            throw new BaseResponseException('消费码不存在');
        }

        $order = Order::findOrFail($order_id);
        if ($order['status'] == Order::STATUS_FINISHED) {
            throw new BaseResponseException('消费码已使用');
        }

        if ($order['status'] == Order::STATUS_PAID) {
            OrderItem::where('order_id', $order_id)
                ->where('merchant_id', request()->get('current_user')->merchant_id)
                ->update(['status' => 2]);

            $order->status = Order::STATUS_FINISHED;
            $order->finish_time = Carbon::now();
            $order->save();
            // 核销完订单之后 进行分润操作
            if($order->status == Order::STATUS_FINISHED){
                OrderFinishedJob::dispatch($order)->onQueue('order:finished')->delay(now()->addSecond(5));
            }

            return $order;
        } else {
            throw new BaseResponseException('该订单已退款，不能核销');
        }
    }

    /**
     * 根据用户ID获取用户下单总数量
     * @param $userId
     * @return int
     */
    public static function getOrderCountByUserId($userId)
    {
        $count = Order::where('user_id', $userId)
            ->whereNotIn('status', [Order::STATUS_UN_PAY, Order::STATUS_CLOSED])
            ->count();
        return $count;
    }

    /**
     * @param $orderNo
     * @return Order
     */
    public static function getInfoByOrderNo($orderNo)
    {
        return Order::where('order_no', $orderNo)->firstOrFail();
    }

    /**
     * @param $orderId
     * @param array $fields
     * @return Order
     */
    public static function getById($orderId, $fields = ['*'])
    {
        return Order::find($orderId, $fields);
    }

    /**
     * 获取订单利润
     * @param $order
     * @return float
     */
    public static function getProfitAmount($order)
    {
        if(is_int($order)){
            $order = self::getById($order);
        }
        $settlementRate = $order->settlement_rate; //分利比例
        // 分利比例要从订单中获取  $order->settlement_rate
        // 计算盈利金额
        $grossProfit = $order->pay_price * $settlementRate / 100;
        $taxAmount = $grossProfit * 0.06 * 1.12 / 1.06 + $grossProfit * 0.1 * 0.25 + 0.0068 * $order->pay_price;

        return max(floor(($grossProfit - $taxAmount) * 100) / 100, 0);
    }

    /**
     * 更新分润状态 和 时间
     * @param $order
     * @return Order
     */
    public static function updateSplittingStatus($order)
    {
        if (is_int($order)) {
            $order = self::getById($order);
        }
        $order->splitting_status = Order::SPLITTING_STATUS_YES;
        $order->splitting_time = Carbon::now();
        $order->save();

        return $order;
    }

    /**
     * 获取退款单信息
     * @param $refundId
     * @param $fields
     * @return OrderRefund
     */
    public static function getRefundById($refundId, $fields = ['*'])
    {
        return OrderRefund::find($refundId, $fields);
    }

    /**
     * 生成退款单号
     * @param int $retry
     * @return string
     */
    public static function genRefundNo($retry = 1000)
    {
        if($retry == 0){
            throw new BaseResponseException('退款单号生成已超过最大重试次数');
        }
        $refundNo = 'R' . date('YmdHis') . rand(1000, 9999);
        if(OrderRefund::where('refund_no', $refundNo)->first()){
            $refundNo = self::genRefundNo(--$retry);
        }
        return $refundNo;
    }

    /**
     * @param $orderNo
     * @param $transactionId
     * @param $totalFee
     * @param int $payType
     * @param Carbon|string $payTime 支付时间
     * @return bool
     */
    public static function paySuccess($orderNo, $transactionId, $totalFee, $payType = Order::PAY_TYPE_WECHAT, $payTime='')
    {
        if (empty($payTime)) {
            $payTime = Carbon::now();
        }
        // 处理订单支付成功逻辑
        $order = OrderService::getInfoByOrderNo($orderNo);

        Log::info('处理订单回调order数据',['order' => $order]);
        if($order->status === Order::STATUS_UN_PAY
            || $order->status === Order::STATUS_CANCEL
            || $order->status === Order::STATUS_CLOSED
        ){
            DB::beginTransaction();
            try{
                $order->pay_type = $payType;
                $order->pay_time = $payTime; // 更新支付时间为当前时间
                if($order->type == Order::TYPE_SCAN_QRCODE_PAY){
                    // 如果是扫码付款, 直接改变订单状态为已完成
                    $order->status = Order::STATUS_FINISHED;
                    $order->finish_time = Carbon::now();
                }else if($order->type == Order::TYPE_DISHES) {
                    $order->status = Order::STATUS_FINISHED;
                    $order->finish_time = Carbon::now();
                }else if ($order->type == Order::TYPE_SUPERMARKET) {
                    $isDeliverySelfMention = ($order->deliver_type == Order::DELIVERY_SELF_MENTION);
                    //超市订单支付成功后如果是配送的订单订单改为待发货
                    $order->status = $isDeliverySelfMention ?
                        Order::STATUS_NOT_TAKE_BY_SELF : Order::STATUS_UNDELIVERED;
                    if ($isDeliverySelfMention) {
                        //超市订单支付成功后如果是到店自取的订单订单改为待取货，并成取货码
                        $order->deliver_code = self::createDeliverCode($order);
                    }
                } else {
                    $order->status = Order::STATUS_PAID;
                }
                if (
                    in_array($order->type, [Order::TYPE_GROUP_BUY, Order::TYPE_SUPERMARKET])
                    && ($order->pintuan_order_id!=0)) {
                    // 新增拼团状态逻辑
                    $order->status = Order::STATUS_PAID_UNSETTLED;
                }
                $order->save();
                if(
                    ($order->type == Order::TYPE_GROUP_BUY)
                    && ($order->pintuan_order_id==0)
                ) {
                    // 拼团团购订单不生成核销码
                    self::makeVerifyCode($order);
                } else if ($order->type == Order::TYPE_DISHES) {
                    //添加菜单已售数量
                    $dishesItems = DishesItem::where('dishes_id',$order->dishes_id)->get();
                    foreach ($dishesItems as $k=>$item){
                        DishesGoods::where('id', $item->dishes_goods_id)->increment('sell_number', max($item->number, 1));
                    }
                }



                // 生成订单支付记录
                $orderPay = new OrderPay();
                $orderPay->order_id = $order->id;
                $orderPay->order_no = $orderNo;
                $orderPay->transaction_no = $transactionId;
                $orderPay->amount = $totalFee;
                $orderPay->save();

                //如果是支付到平台的订单，产生一条交易流水
                if ($order->pay_target_type != Order::PAY_TARGET_TYPE_OPER ) {
                    $platform_trade_record = new PlatformTradeRecord();
                    $platform_trade_record->type = PlatformTradeRecord::TYPE_PAY;
                    $platform_trade_record->merchant_type = $order->merchant_type;
                    $platform_trade_record->pay_id = 1;
                    $platform_trade_record->trade_amount = $totalFee;
                    $platform_trade_record->trade_time = $payTime;
                    $platform_trade_record->trade_no = $transactionId;
                    $platform_trade_record->order_no = $orderNo;
                    $platform_trade_record->oper_id = $order->oper_id;
                    $platform_trade_record->merchant_id = $order->merchant_id;
                    $platform_trade_record->user_id = $order->user_id;
                    $platform_trade_record->remark = '';
                    $platform_trade_record->save();
                }

                //如果是超市商户，更新商户当月销量
                if ($order->type == Order::TYPE_SUPERMARKET) {
                    CsStatisticsMerchantOrderService::addMerchantOrderNumberToday($order->merchant_id);
                }

                DB::commit();
            }catch (\Exception $e){
                DB::rollBack();
                Log::error('订单支付成功回调操作失败,失败信息:'.$e->getMessage(), $e->getTrace());
                return false;
            }

            try {
                // 支付成功, 如果用户没有被邀请过, 将用户的邀请人设置为当前商户
                // 需要同步执行, 以防止执行订单完成任务的分润时, 商户还没绑定为用户的邀请人, 同时, 该操作执行失败不影响订单支付完成状态
                $userId = $order->user_id;
                if( empty( InviteUserRecord::where('user_id', $userId)->first() ) ){
                    if ($order->merchant_type == Order::MERCHANT_TYPE_SUPERMARKET) {
                        if ($order->share_user_id && $order->share_user_id != $order->user_id) {
                            $originId = $order->share_user_id;
                            $originType = InviteChannel::ORIGIN_TYPE_USER;
                            $operId = 0;
                        } else {
                            $originId = $order->merchant_id;
                            $merchant = CsMerchantService::getById($originId);
                            if(empty($merchant)){
                                throw new DataNotFoundException('超市商户信息不存在');
                            }
                            $originType = InviteChannel::ORIGIN_TYPE_CS_MERCHANT;
                            $operId = $merchant->oper_id;
                        }
                    } else {
                        $originId = $order->merchant_id;
                        $merchant = MerchantService::getById($originId);
                        if(empty($merchant)){
                            throw new DataNotFoundException('商户信息不存在');
                        }
                        $originType = InviteChannel::ORIGIN_TYPE_MERCHANT;
                        $operId = $merchant->oper_id;
                    }
                    $inviteChannel = InviteChannelService::getByOriginInfo($originId, $originType, $operId);
                    InviteUserService::bindInviter($userId, $inviteChannel);
                }

            }catch (\Exception $e){
                // 此操作不影响流程, 捕捉异常之后不做其他操作
                Log::error('订单支付完成后, 绑定商户为用户的邀请人操作失败', compact('order', 'merchant'));
            }

            // 提交事务之后再派发任务, 防止任务处理时订单状态还未修改
            OrderPaidJob::dispatch($order);

            // 如果订单是已完成的状态, 分发一个订单完成的任务, 读写分离, 必须在修改状态的地方发任务, 否则可能会造成丢单
            if($order->status == Order::STATUS_FINISHED){
                OrderFinishedJob::dispatch($order)->onQueue('order:finished')->delay(now()->addSecond(5));
            }

            // 拼团逻辑
            self::handleGroupBookOrder($order);

            // 信鸽推送通知
            XingePushOrderInfoJob::dispatch($order);
            // 短信通知, 如果是拼团订单, 不发送短信通知
            if($order->marketing_id != 2){
                SmsVerifyCodeService::sendBuySuccessNotify($orderNo);
            }

            return true;
        }else if($order->status == Order::STATUS_PAID){
            // 已经支付成功了
            return true;
        }else if($order->status == Order::STATUS_REFUNDING
            || $order->status === Order::STATUS_REFUNDED
            || $order->status === Order::STATUS_FINISHED
        ){
            // 订单已退款或已完成
            return true;
        }
        return false;
    }

    public static function makeVerifyCode($order)
    {
        // 添加商品已售数量
        Goods::where('id', $order->goods_id)->increment('sell_number', max($order->buy_number, 1));
        // 生成核销码, 线上需要放到支付成功通知中
        $verify_code = OrderItem::createVerifyCode($order->merchant_id);
        // todo 一个订单只生成一个核销码
        for ($i = 0; $i < $order->buy_number; $i ++){
            $orderItem = new OrderItem();
            $orderItem->oper_id = $order->oper_id;
            $orderItem->merchant_id = $order->merchant_id;
            $orderItem->order_id = $order->id;
            $orderItem->verify_code = $verify_code;
            $orderItem->status = 1;
            $orderItem->save();
        }
    }


    public static function handleGroupBookOrder($order)
    {
        $isNeedSendGroupBook = (in_array($order->type, [
                Order::TYPE_GROUP_BUY,
                Order::TYPE_SUPERMARKET,
            ]) && $order->marketing_id == MarketingApi::MARKETING_GROUP_BOOK);
        if ($isNeedSendGroupBook) {
            // 通知拼团数据更新
            $res = MarketingApi::informRecordOrderToMarketingGroupBook($order);
            if ($res==false) {
                Log::error('拼团订单异常', json_encode($order));
                return;
            }
            if ( isset($res['is_refund']) && ($res['is_refund']=='refund') ) {
                // 参团失败，直接退款
                OrderService::manualRefund($order->id);
            }
            if (isset($res['is_full']) && $res['is_full']=='yes') {
                OrderService::setGroupBookOrderSuccess($order->pintuan_order_id);
            }
        }
    }

    /**
     * 超市订单发货
     * @param $orderId
     * @param $expressCompany
     * @param $expressNo
     * @return Order
     */
    public static function deliver($orderId, $expressCompany, $expressNo)
    {
        $order = self::getById($orderId);
        if (empty($order)) {
            throw new BaseResponseException('该订单不存在');
        }
        if ($order->status != Order::STATUS_UNDELIVERED) {
            throw new BaseResponseException('该订单状态不是待发货');
        }
        if ($order->status == Order::STATUS_DELIVERED) {
            throw new BaseResponseException('该订单已发货');
        }
        $order->express_company = $expressCompany;
        $order->express_no = $expressNo;
        $order->deliver_time = Carbon::now();
        $order->status = Order::STATUS_DELIVERED;
        $order->save();

        DeliveredOrderAutoFinishedJob::dispatch($order)->delay(Carbon::now()->addDays(7));

        return $order;
    }

    /**
     * 根据订单号 发货 批量发货使用
     * @param $orderNo
     * @param $merchantId
     * @param $expressCompany
     * @param $expressNo
     * @return Order
     * @throws \Exception
     */
    public static function deliverByOrderNo($orderNo, $merchantId, $expressCompany, $expressNo)
    {
        $order = Order::where('order_no', $orderNo)
            ->where('merchant_id', $merchantId)
            ->first();
        if (empty($order)) {
            throw new \Exception("订单号为{$orderNo}的订单不存在");
        }
        if (!$expressCompany) {
            throw new \Exception("订单号为{$orderNo}的订单的快递公司不能为空");
        }
        if (!$expressNo) {
            throw new \Exception("订单号为{$orderNo}的订单的快递单号不能为空");
        }
        if ($order->status != Order::STATUS_UNDELIVERED) {
            throw new \Exception("订单号为{$orderNo}的订单的状态不是待发货");
        }
        $preg = '/[.e]/';
        if (preg_match($preg, $expressNo)) {
            throw new \Exception("订单号为{$orderNo}的订单的快递单号{$expressNo}有误");
        }
        $order->express_company = $expressCompany;
        $order->express_no = $expressNo;
        $order->deliver_time = Carbon::now();
        $order->status = Order::STATUS_DELIVERED;
        $order->save();

        DeliveredOrderAutoFinishedJob::dispatch($order)->delay(Carbon::now()->addDays(7));

        return $order;
    }

    /**
     * 核销 超市订单 取货码
     * @param $orderId
     * @param $deliverCode
     * @return Order
     */
    public static function verifyCsOrder($orderId, $deliverCode)
    {
        $order = self::getById($orderId);
        if (empty($order)) {
            throw new BaseResponseException('该订单不存在');
        }
        if ($order->status != Order::STATUS_NOT_TAKE_BY_SELF) {
            throw new BaseResponseException('该订单已发货');
        }
        if ($order->deliver_code == $deliverCode) {
            $order->deliver_time = Carbon::now();
            $order->status = Order::STATUS_FINISHED;
            $order->finish_time = Carbon::now();
            $order->take_delivery_time = Carbon::now();
            $order->save();
            OrderFinishedJob::dispatch($order)->onQueue('order:finished')->delay(now()->addSecond(5));
        } else {
            throw new BaseResponseException('无效取货码');
        }

        return $order;
    }

    /**
     * 检查超市订单 取货码
     * @param $orderId
     * @param $deliverCode
     * @return Order
     */
    public static function checkDeliverCode($orderId, $deliverCode)
    {
        $order = self::getById($orderId);
        if (empty($order)) {
            throw new BaseResponseException('该订单不存在');
        }
        if ($order->status != Order::STATUS_NOT_TAKE_BY_SELF) {
            throw new BaseResponseException('该订单已发货');
        }

        if ($order->deliver_code == $deliverCode) {
            return $order;
        } else {
            throw new BaseResponseException('无效取货码');
        }
    }


    /**
     * 生成取货码（暂时同一个商户唯一）
     * @param Order $order
     * @return int
     */
    public static function createDeliverCode(Order $order)
    {
        $code = rand(100000,999999);
        $exist = Order::where('deliver_code',$code)->where('merchant_id',$order->merchant_id)->first();
        if ($exist) {
            $code = self::createDeliverCode($order);
        }
        return $code;
    }

    /**
     * 用户确认收货
     * @param $order_no
     * @param $user_id
     * @return Order
     */
    public static function userConfirmDelivery($order_no, $user_id)
    {
        $order = Order::where('order_no', $order_no)->first();
        if (empty($order)) {
            throw new BaseResponseException('该订单不存在');
        }
        if ($order->user_id != $user_id) {
            throw new BaseResponseException('非法操作');
        }
        if ($order->status != Order::STATUS_DELIVERED) {
            throw new BaseResponseException('不是已发货的订单不能确认收货');
        }

        $order->take_delivery_time =  Carbon::now();
        $order->finish_time = Carbon::now();
        $order->status = Order::STATUS_FINISHED;
        $order->save();
        OrderFinishedJob::dispatch($order)->onQueue('order:finished')->delay(now()->addSecond(5));

        return $order;
    }

    /**
     * 删除订单
     * @param $order_no
     * @param $user_id
     * @return Order
     */
    public static function userDel($order_no, $user_id)
    {
        $order = Order::where('order_no', $order_no)->first();
        if (empty($order)) {
            throw new BaseResponseException('该订单不存在');
        }
        if ($order->user_id != $user_id) {
            throw new BaseResponseException('非法操作');
        }
        if (!in_array($order->status,[Order::STATUS_FINISHED,Order::STATUS_CANCEL,Order::STATUS_CLOSED,Order::STATUS_REFUNDED])) {
            throw new BaseResponseException('订单不满足删除条件');
        }
        $order->user_deleted_at = Carbon::now();
        $order->save();
        return $order;
    }

    /**
     * 判断商品库存 并 返回商品价格
     * @param CsMerchant $merchant
     * @param $goodsList
     * @param int $throw
     * @return float|int
     */
    public static function checkGoodsStockAndReturnPrice(CsMerchant $merchant, $goodsList, $throw = 0)
    {
        if ($merchant->status == CsMerchant::STATUS_OFF){
            throw new BaseResponseException('该超市已下架，请选择其他商户下单', ResultCode::CS_MERCHANT_OFF);
        }

        if (is_string($goodsList)) {
            $goodsList = json_decode($goodsList, true);
        }

        if (empty($goodsList)) {
            throw new ParamInvalidException('商品列表为空');
        }
        if (sizeof($goodsList) < 1) {
            throw new ParamInvalidException('参数不合法1');
        }
        if (sizeof($goodsList) > 1) {
            throw new ParamInvalidException('因过年期间物流系统升级，每个订单限制购买一种商品，望理解');
        }
        $goodsPrice = 0;
        foreach ($goodsList as $item) {
            if (!isset($item['id']) || !isset($item['number']) || $item['number']<=0) {
                throw new ParamInvalidException('参数不合法2');
            }
            $good = CsGood::findOrFail($item['id']);
            if ($good->status == CsGood::STATUS_OFF) {
                throw new BaseResponseException('订单中 ' . $good->goods_name . ' 已下架，请删除后重试');
            }
            if ($good->stock<=0 || $good->stock < $item['number']) {
                if ($throw) {
                    throw new BaseResponseException('订单中商品 ' . $good->goods_name . ' 库存不足，请删除后重试', ResultCode::CS_GOODS_STOCK_NULL);
                } else {
                    return Result::error(ResultCode::CS_GOODS_STOCK_NULL, '订单中商品 ' . $good->goods_name . ' 库存不足，请删除后重试', ['goods_id' => $item['id']]);
                }

            }
            $goodsPrice += ($good->price) * ($item['number']);
        }

        return $goodsPrice;
    }

    /**
     * 统计活动商品总金额
     * @param $discountGoodsList
     * @param $goodsList
     * @param $clientGoodNNumberList
     * @return float|int
     */
    public static function computedDiscountMoneyForPhase($discountGoodsList,$goodsList,$clientGoodNNumberList)
    {
        $goodsPrice = 0;
        foreach ($goodsList as $good) {
            $userOrderNum = $good->user_order_num;                              // 用户该商品的总数量
            if(array_key_exists($good->id,$discountGoodsList)){
                $canGoodLimited = $discountGoodsList[$good->id]['limited'];     // 营销端传来活动可购买数量
                $userUploadLimited = $clientGoodNNumberList[$good->id];         // 客户端传来可购买数量
                $canGoodLimited = ($userUploadLimited<$canGoodLimited) ? $userUploadLimited:$canGoodLimited;
                $discountNumber = ($userOrderNum>$canGoodLimited) ? $canGoodLimited:$userOrderNum;
                $goodsPrice += (float)$discountGoodsList[$good->id]['discount_price']*$discountNumber;
                $userOrderNum -= $discountNumber;
                $good->discountNumber = $discountNumber;
            }
            if($userOrderNum>0){
                $goodsPrice += ($good->price) * ($userOrderNum);
            }
        }
        return $goodsPrice;
    }


    /**
     * 格式化 历时
     * @param $endTime
     * @param $startTime
     * @return string
     */
    public static function formatTime($endTime, $startTime)
    {
        $time = $endTime - $startTime;
        $hours = floor($time/3600);
        $minutes = floor(($time/60) % 60);
        $second = floor($time%60);
        $text = $hours . '时' . $minutes . '分' . $second . '秒';
        return $text;
    }


    /***
     * 统计用户各种状态的订单
     * @param $user_id
     * @param int $merchantShareInMiniprogram
     * @param int $currentOperId
     * @return array
     */
    public static function getUserCounts($user_id,$merchantShareInMiniprogram = 1,$currentOperId=0)
    {
        $status_map = [
            'a' => Order::STATUS_UN_PAY, //待付款
            'b' => Order::STATUS_UNDELIVERED,//代发货
            'c' => Order::STATUS_DELIVERED,//待收货
            'd' => [Order::STATUS_PAID,Order::STATUS_NOT_TAKE_BY_SELF],//待使用
            //'e' => [Order::STATUS_CLOSED,Order::STATUS_FINISHED],//已完成
            //'f' => Order::STATUS_REFUNDED,//已退款
        ];

        $supermarket_on = SettingService::getValueByKey('supermarket_on');
        if (empty($supermarket_on)) {
            $status_map = [
                'a' => 0, //待付款
                'b' => 0,//代发货
                'c' => 0,//待收货
                'd' => 0,//待使用
            ];
            return $status_map;
        }


        foreach ($status_map as $k=>$v) {
            //只能查询支付到平台的订单
            $status_map[$k] = Order::where('user_id', $user_id)
                ->where('pay_target_type',Order::PAY_TARGET_TYPE_PLATFORM)
                ->whereNull('user_deleted_at')
                ->where('pintuan_order_id', 0)
                ->where(function (Builder $query) {
                    $query->where('type', Order::TYPE_GROUP_BUY)
                        ->orWhere(function (Builder $query) {
                            $query->where('type', Order::TYPE_SCAN_QRCODE_PAY)
                                ->whereIn('status', [4, 6, 7]);
                        })->orWhere('type', Order::TYPE_DISHES)
                        ->orWhere('type', Order::TYPE_SUPERMARKET);

                })
                ->when($merchantShareInMiniprogram != 1, function (Builder $query) use ($currentOperId) {
                    $query->where('oper_id', $currentOperId);
                })
                ->when($v, function (Builder $query) use ($v) {
                    if (is_array($v)) {
                        $query->whereIn('status',$v);
                    } else {
                        $query->where('status', $v);
                    }

                })
                ->count();
        }

        return $status_map;
    }

    /**
     * 提醒发货
     * @param $orderId
     * @return Order
     */
    public static function reminderDeliver($orderId)
    {
        $order = self::getById($orderId);
        if (empty($order)) {
            throw new BaseResponseException('该订单不存在');
        }
        if ($order->status != Order::STATUS_UNDELIVERED) {
            throw new BaseResponseException('该订单状态不是待发货');
        }
        if ($order->reminder_time && (time() - strtotime($order->reminder_time) < 30 * 60)) {
            throw new BaseResponseException('您已提醒发货，请稍后提醒');
        } else {
            $order->reminder_time = Carbon::now();
        }

        return $order;
    }

    public static function makeOrderBasic($user,$merchant)
    {
        $order = new Order();
        $order->order_no = Order::genOrderNo();
        $order->user_id = $user->id;
        $order->user_name = $user->name ?? '';
        $order->notify_mobile = $user->mobile;

        $order->merchant_name = $merchant->signboard_name ?? '';
        $order->oper_id = $merchant->oper_id;
        $order->merchant_id = $merchant->id;
        $order->settlement_rate = $merchant->settlement_rate;
        $order->bizer_id = $merchant->bizer_id;
        $order->status = Order::STATUS_UN_PAY;
        return $order;
    }

    /**
     * @param $order
     * 还原库存
     */
    public static function decSellNumber($order)
    {
        if ($order->type == Order::TYPE_GROUP_BUY){
            Goods::where('id', $order->goods_id)
                ->where('merchant_id', $order->merchant_id)
                ->decrement('sell_number', $order->buy_number);
        }elseif ($order->type == Order::TYPE_DISHES){
            $dishesItems = DishesItem::where('merchant_id', $order->merchant_id)
                ->where('dishes_id', $order->dishes_id)
                ->get();
            foreach ($dishesItems as $item){
                DishesGoods::where('id', $item->dishes_goods_id)
                    ->where('merchant_id', $item->merchant_id)
                    ->decrement('sell_number', $item->number);
            }
        }
    }

    /**
     * @param $orderPay OrderPay
     * @param $order    Order
     * @param $payClass AliPay | WechatPay
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public static function refund($orderPay, $order, $payClass)
    {
        DB::beginTransaction();
        try{
            $orderRefund = new OrderRefund();
            $orderRefund->refund_no = OrderService::genRefundNo();
            $orderRefund->order_id = $order->id;
            $orderRefund->order_no = $order->order_no;
            $orderRefund->amount = $orderPay->amount;
            $orderRefund->save();
            $result = $payClass->handleRefund($order,$orderPay,$orderRefund);
            $logTitle = '';
            if($payClass instanceof AliPay){
                // 如果为支付宝支付
                $orderRefund->refund_id = '';
                $orderRefund->status = OrderRefund::STATUS_SUCCESS;
                $orderRefund->save();
                $logTitle = '支付宝';
            }
            if(!$result){
                Log::error($logTitle.'退款失败 :', [
                    'result' => $result,
                    'params' => [
                        'orderPay' => $orderPay->toArray(),
                        'orderRefund' => $orderRefund->toArray(),
                    ]
                ]);
                throw new BaseResponseException($logTitle.'退款失败');
            }


            $order->status = Order::STATUS_REFUNDED;
            $order->refund_time = Carbon::now();
            $order->refund_price = $orderPay->amount;
            $order->save();

            $platform_trade_record = new PlatformTradeRecord();
            $platform_trade_record->type = PlatformTradeRecord::TYPE_REFUND;
            $platform_trade_record->pay_id = $order->pay_type;
            $platform_trade_record->trade_amount = $orderPay->amount;
            $platform_trade_record->trade_time = $order->refund_time;
            $platform_trade_record->trade_no = $orderRefund->refund_id;
            $platform_trade_record->order_no = $order->order_no;
            $platform_trade_record->oper_id = $order->oper_id;
            $platform_trade_record->merchant_id = $order->merchant_id;
            $platform_trade_record->user_id = $order->user_id;
            $platform_trade_record->remark = '';
            $platform_trade_record->save();
            //如果是超市商户，更新商户当月销量
            if ($order->type == Order::TYPE_SUPERMARKET) {
                CsStatisticsMerchantOrderService::minusCsMerchantOrderNumberToday($order->merchant_id);
                CsOrderGoodService::orderCancel($order->id);
            }
            DB::commit();
            return Result::success($orderRefund);
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }
    }

    public static function getTextName(&$list)
    {
        $list->each(function($item){
            $goodName  = $item->goods_name;
            if($item->type == Order::TYPE_DISHES ){
                $goodItems = DishesItem::where('dishes_id', $item->dishes_id)->get();
            }elseif ($item->type == Order::TYPE_SCAN_QRCODE_PAY){
                $goodItems = '无';
                $goodName  = '';
            }elseif ($item->type == Order::TYPE_SUPERMARKET) {
                $goodItems = CsOrderGood::where('order_id',$item->id)->get();
                $goodName  = '';
            }else{
                $goodItems = Goods::where('id',$item->goods_id)->get();
            }
            $item->goods_name = Order::getGoodsNameText($item->type,$goodItems,$goodName);
        });
    }

    /**
     * 获取可优惠的活动
     * @param $phaseId
     * @param $marketingId
     * @param $phaseGoodsList
     * @param $merchantId
     * @param $userId
     * @param $goodsType
     * @param array|'' $userLimitedList      前端传过来的数据
     * @return array
     */
    public static function getCanDiscountGoodsList($phaseId,$marketingId,$phaseGoodsList,$merchantId,$userId,$goodsType,$userLimitedList)
    {
        $canDiscountGoodsList = [];
        $isCanDiscount = ($phaseId && $marketingId && (!empty($phaseGoodsList) ));
        if($isCanDiscount){
            $tempCanDiscountGoodsList = MarketingApi::getCanDiscountGoodsFromMarketing($merchantId,$phaseGoodsList,$phaseId,$marketingId,$goodsType,$userId);
            if($tempCanDiscountGoodsList && !empty($tempCanDiscountGoodsList['list'])){
                $canDiscountGoodsList = $tempCanDiscountGoodsList['list'];
            }
            self::checkUserLimitedIsBeyondForSupermarketOrder($userLimitedList,$canDiscountGoodsList);
        }

        return $canDiscountGoodsList;
    }

    public static function supermarketGroupBook()
    {

    }

    /**
     * 检出用户优惠名额是否超出
     * @param $userLimitedList
     * @param $canDiscountGoodsList
     */
    public static function checkUserLimitedIsBeyondForSupermarketOrder($userLimitedList,$canDiscountGoodsList)
    {
        foreach ($userLimitedList as $goodId=>$limited){
            $canLimitedBuy = isset($canDiscountGoodsList[$goodId]) ? $canDiscountGoodsList[$goodId] : 0;
            $checkUserLimitedIsBeyond = ($limited<$canLimitedBuy);
            if(!$checkUserLimitedIsBeyond){
                $num = ($canLimitedBuy<0)?0:$canLimitedBuy;
                throw new BaseResponseException('当前活动已结束，请重新选购',ResultCode::MARKETING_OVER_USER_LIMIT);
            }
        }
    }

    public static function getCanDiscountGoodsListForGroup($phaseId,$marketingId,$discountNumber,$merchantId,$userId,$goodsType,$goodsId,$intUserLimited)
    {
        $canDiscountGoodsList = [];
        $isCanDiscount = ($phaseId&&$marketingId && $discountNumber>0);
        if($isCanDiscount){
            $tempCanDiscountGoodsList = MarketingApi::getCanDiscountGoodsFromMarketing($merchantId,[$goodsId],$phaseId,$marketingId,$goodsType,$userId);
            if($tempCanDiscountGoodsList && !empty($tempCanDiscountGoodsList['list'])){
                $canDiscountGoodsList = $tempCanDiscountGoodsList['list'][$goodsId];
            }
            self::checkUserLimitedIsBeyondForGroupOrder($intUserLimited,$canDiscountGoodsList);
        }
        return $canDiscountGoodsList;
    }

    public static function checkUserLimitedIsBeyondForGroupOrder($intUserLimited,$canDiscountGoodsList)
    {
        if(!isset($canDiscountGoodsList['limited'])) throw new BaseResponseException('当前活动已结束，请重新选购',ResultCode::MARKETING_OVER_USER_LIMIT);
        $checkUserLimitedIsBeyond = ($intUserLimited>$canDiscountGoodsList['limited']);
        if($checkUserLimitedIsBeyond){
            $num = ($canDiscountGoodsList['limited']<0)?0:$canDiscountGoodsList['limited'];
            throw new BaseResponseException('当前活动已结束，请重新选购',ResultCode::MARKETING_OVER_USER_LIMIT);
        }
    }

    public static function checkGroupBookTeamValidate($groupDetail, $userId, $groupBookTeamId)
    {
        if (!$groupDetail) {
            throw new BaseResponseException('当前商品并不能超值拼团，请重新选购');
        }

        $groupBookOrder = MarketingApi::getGroupBookOrderById($groupBookTeamId);
        $timeoutValidate = (strtotime($groupBookOrder['created_at']) + 60 * 60 * 24) < time();
        if ($timeoutValidate) {
            throw new BaseResponseException('当前团已超过24小时有效期，请重新选购');
        }
        $count = count($groupBookOrder['records_list']);
        $userIds = array_column($groupBookOrder['records_list'], 'user_id');
        if( in_array($userId, $userIds) ) {
            throw new BaseResponseException('不可重复参团哦');
        }
        $teamResidue = $groupDetail['pintuan_people_number'] - $count;
        if ($teamResidue <= 0) {
            throw new BaseResponseException('拼团人数已满，请重新选购', ResultCode::AUTH_GROUP_NOT_FOUND);
        }
    }

    public static function getCanGroupBookListForGroup($phaseId,$marketingId,$groupBookNumber, $goodsId, $groupBookTeamId, $userId)
    {
        if ($groupBookTeamId != 0) {
            $groupDetail = MarketingApi::getGroupBookingGoodsFromMarketingByTeamId($groupBookTeamId);
            self::checkGroupBookTeamValidate($groupDetail, $userId, $groupBookTeamId);
        } else {
            $groupDetail = MarketingApi::getGroupBookingGoodsFromMarketingById(1,$goodsId);
            self::checkGroupBookValidate($groupDetail, $groupBookTeamId, $groupBookNumber);
        }

        return [
            'discount_price' => $groupDetail['discount_price'],
            'marketing_goods_id' => $groupDetail['id']
        ];
    }

    public static function checkGroupBookValidate($groupDetail, $groupBookTeamId, $groupBookNumber)
    {
        if (!$groupDetail) {
            throw new BaseResponseException('当前商品并不能超值拼团，请重新选购');
        }
        if (($groupBookTeamId==0)
            && ($groupDetail['team_count']>=$groupDetail['pintuan_total_count'])
        ) {
            throw new BaseResponseException('开团达到上限，请重新选购', ResultCode::RULE_NOT_FOUND);
        }
        $goodsResidue = (int)$groupDetail['stock'] - (int)$groupDetail['sales_count'];
        $limitedNum = min($goodsResidue, $groupDetail['limited']);
        //商品剩余库存
        if ($groupBookNumber > $limitedNum) {
            throw new BaseResponseException('超出限购量');
        }
    }

    public static function getCanGroupBookListForSupermarket($detail, $groupBookNumber, $groupBookTeamId, $userId)
    {
        if ($groupBookTeamId != 0) {
            $groupDetail = MarketingApi::getGroupBookingGoodsFromMarketingByTeamId($groupBookTeamId);
            self::checkGroupBookTeamValidate($groupDetail, $userId, $groupBookTeamId);
        } else {
            $groupDetail = MarketingApi::getGroupBookingGoodsFromMarketingById(3,$detail->id);
            self::checkGroupBookValidate($groupDetail, $groupBookTeamId, $groupBookNumber);
        }
        return [
            'discount_price' => $groupDetail['discount_price'],
            'marketing_goods_id' => $groupDetail['id']
        ];
    }

    /**
     * 通过结算单id和支付目标类型来查找订单
     * @param $settlementId
     * @param $payTargetType
     * @return Order[]|Collection
     */
    public static function getListBySettlementIdAndPayTargetType($settlementId, $payTargetType)
    {
        $data = Order::where('settlement_id', $settlementId)
            ->where('pay_target_type', $payTargetType)
            ->orderBy('id', 'desc')
            ->get();
        return $data;
    }

    /**
     * 商户端app 订单列表
     * @param $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function merchantAppGetOrderList($params)
    {
        $merchantId = array_get($params, 'merchantId');
        $type = array_get($params, 'type');
        $status = array_get($params, 'status');
        $verifyStatus = array_get($params, 'verifyStatus');
        $pageSize = array_get($params, 'pageSize', 10);

        $query = Order::where('merchant_id', $merchantId)
            ->where('merchant_type', Order::MERCHANT_TYPE_NORMAL);

        if ($type) {
            $query->where('type', $type);
        }
        if ($status) {
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }
        if ($verifyStatus) {
            if ($verifyStatus = OrderItem::STATUS_UN_VERIFY) {
                $query->whereNotIn('status', [Order::STATUS_FINISHED, Order::STATUS_REFUNDED]);
            }
            $query->whereHas('orderItems', function (Builder $query) use ($verifyStatus) {
                $query->where('status', $verifyStatus);
            });
        }

        $query->with('dishesItems');
        $query->with('user:id,name,mobile,avatar_url,wx_nick_name,wx_avatar_url');
        $query->orderBy('created_at', 'desc');
        $data = $query->paginate($pageSize);

        return $data;
    }

    /**
     * 订单核销 带订单id
     * @param $merchantId
     * @param $verifyCode
     * @param $orderId
     * @return Order
     */
    public static function verifyOrderWithOrderId($merchantId, $verifyCode, $orderId)
    {
        $order = OrderService::getById($orderId);
        if (empty($order)) {
            throw new BaseResponseException('该订单不存在');
        }
        $orderItems = OrderItem::where('verify_code', $verifyCode)
            ->where('merchant_id', $merchantId)
            ->where('order_id', $orderId)
            ->get();
        if ($orderItems->isEmpty()) {
            throw new BaseResponseException('此消费码与该订单不匹配');
        }

        if ($order['status'] == Order::STATUS_FINISHED) {
            throw new BaseResponseException('消费码已使用');
        }

        if ($order['status'] == Order::STATUS_PAID) {
            OrderItem::where('order_id', $orderId)
                ->where('verify_code', $verifyCode)
                ->where('merchant_id', $merchantId)
                ->update(['status' => OrderItem::STATUS_VERIFY]);

            $order->status = Order::STATUS_FINISHED;
            $order->finish_time = Carbon::now();
            $order->save();
            // 核销完订单之后 进行分润操作
            if($order->status == Order::STATUS_FINISHED){
                OrderFinishedJob::dispatch($order)->onQueue('order:finished')->delay(now()->addSecond(5));
            }

            return $order;
        } else {
            throw new BaseResponseException('该订单已退款，不能核销');
        }
    }

    /**
     * 检查核销码
     * @param $merchantId
     * @param $verifyCode
     * @return Order
     */
    public static function checkVerifyOrder($merchantId, $verifyCode)
    {
        $order_id = OrderItem::where('verify_code', $verifyCode)
            ->where('merchant_id', $merchantId)
            ->value('order_id');

        if (!$order_id) {
            throw new BaseResponseException('消费码不存在');
        }

        $order = self::getById($order_id);
        if (empty($order)) {
            throw new BaseResponseException('该订单不存在');
        }
        if ($order['status'] == Order::STATUS_FINISHED) {
            throw new BaseResponseException('消费码已使用');
        }

        if ($order['status'] != Order::STATUS_PAID) {
            throw new BaseResponseException('该订单已退款，不能核销');
        }
        return $order;
    }

    /**
     * 获取超市下单商品列表，并注入对应数量
     * @param CsMerchant $merchant
     * @param $goodsList
     * @return array
     */
    public static function getCsOrderGoodsList(CsMerchant $merchant, $goodsList)
    {
        if ($merchant->status == CsMerchant::STATUS_OFF){
            throw new BaseResponseException('该超市已下架，请选择其他商户下单', ResultCode::CS_MERCHANT_OFF);
        }
        if (is_string($goodsList)) {
            $goodsList = json_decode($goodsList, true);
        }
        if (empty($goodsList)) {
            throw new ParamInvalidException('商品列表为空');
        }
        if (sizeof($goodsList) < 1) {
            throw new ParamInvalidException('参数不合法1');
        }
        if (sizeof($goodsList) > 1) {
            throw new ParamInvalidException('因过年期间物流系统升级，每个订单限制购买一种商品，望理解');
        }
        $returnGoodsList = [];
        foreach ($goodsList as $item) {
            if (!isset($item['id']) || !isset($item['number']) || $item['number']<=0) {
                throw new ParamInvalidException('参数不合法2');
            }
            $good = CsGood::findOrFail($item['id']);
            if ($good->status == CsGood::STATUS_OFF) {
                throw new BaseResponseException('订单中 ' . $good->goods_name . ' 已下架，请删除后重试');
            }
            if ($good->stock<=0 || $good->stock < $item['number']) {
                throw new BaseResponseException('订单中商品 ' . $good->goods_name . ' 库存不足，请删除后重试', ResultCode::CS_GOODS_STOCK_NULL);
            }
            $good->user_order_num = $item['number'];         // 插入下单数额
            $returnGoodsList[] = $good;
        }
        return $returnGoodsList;
    }

    /**
     * 获取超市订单原价商品
     * @param $goodsList
     * @return float|int
     */
    public static function getCsOrderGoodsPrice($goodsList){
        $goodsPrice = 0;
        foreach ($goodsList as $good){
            $goodsPrice += $good->price*$good->user_order_num;
        }
        return $goodsPrice;
    }

    /**
     * 获取限时抢购活动商品列表, 用于向营销系统同步数据
     * @param Order $order
     * @return array
     */
    public static function getPhaseGoodsList(Order $order)
    {
        switch ($order->type){
            case Order::TYPE_GROUP_BUY:
                $orderGoodsList = [];
                $orderGoodsList[] = [
                    'goods_id'  =>  $order->goods_id,
                    'price'     =>  $order->original_price,
                    'discount_price'=>$order->price,
                    'buy_number'=>  $order->discount_number,
                    'total_price'=> $order->price*$order->discount_number,
                    'goods_type'=>  OrderService::MARKETING_GOODS_TYPE_GROUP,
                ];
                break;
            case Order::TYPE_DISHES:
                $orderGoodsList = DishesGoodsService::getPhaseGoodsList($order->dishes_id,$order->marketing_id,$order->phase_id);
                break;
            case Order::TYPE_SUPERMARKET:
                $orderGoodsList = CsOrderGoodService::getPhaseGoodsList($order->id,$order->marketing_id,$order->phase_id);
                break;
            default:
                throw new BaseResponseException('非营销端合法商品类型');
        }
        return $orderGoodsList;
    }


    /**
     * 订单取消还库存
     * @param Order $order
     * @return bool
     */
    public static function orderCancel(Order $order)
    {

        //Log::error($order);
        switch ($order->type){
            case Order::TYPE_GROUP_BUY:

                $goods = Goods::find($order->goods_id);
                if ($goods) {
                    $goods->stock += $order->buy_number;
                    $goods->save();
                }
                break;
            case Order::TYPE_DISHES:
                DishesGoodsService::orderCancel($order->dishes_id);
                break;
            case Order::TYPE_SUPERMARKET:
                CsOrderGoodService::orderCancel($order->id);
                break;
            default:
                break;
        }
        return true;
    }

    public static function getGroupBookList($userId, $groupBookStatus, $currentOperId, $lng, $lat)
    {
        $query =  Order::whereNull('user_deleted_at')
            ->where('user_id',$userId);

        if($groupBookStatus) {
            $statusOrders = MarketingApi::getUserOrdersByStatus($userId, $groupBookStatus);
            $whereStatus = [-1];
            if($statusOrders) {
                $whereStatus = array_column($statusOrders, 'pintuan_order_id');
            }
            $query = $query->whereIn('pintuan_order_id', $whereStatus);
        }else{
            $query->where('pintuan_order_id','!=',0);
        }
        $query->orderBy('id', 'desc');
//        $orderIds = $query->get('order_id');
        $data = $query->paginate();
        $data->each(function ($item) use ($currentOperId, $lng, $lat) {
            if($item->type == Order::TYPE_SUPERMARKET) {
                //注入超市商户信息
                $item->items = OrderItem::where('order_id', $item->id)->get();
                $item->goods_end_date = '';
                if ($item->type == Order::TYPE_DISHES) {
                    $item->dishes_items = DishesItem::where('dishes_id', $item->dishes_id)
                        ->with('dishes_goods:id,dishes_category_id')
                        ->get();
                    $item->order_goods_number = DishesItem::where('dishes_id',$item->dishes_id)->sum('number');
                }else if($item->type == Order::TYPE_GROUP_BUY){
                    $goods = Goods::withTrashed()->where('id', $item->goods_id)->first();
                    $item->goods_end_date = $goods->end_date;
                    $item->goods_price = $goods->price;

                    if($lng && $lat){
                        $distance = Lbs::getDistanceOfMerchant($item->merchant->id, request()->get('current_open_id'), floatval($lng), floatval($lat));
                        // 格式化距离
                        $item->merchant->distance = Utils::getFormativeDistance($distance);
                    }
                }else if($item->type == Order::TYPE_SUPERMARKET){
                    $item->order_goods_number = CsOrderGood::where('order_id',$item->id)->sum('number');
                    $item->order_goods = CsOrderGood::where('order_id',$item->id)->with('cs_goods:id,logo')->get();
                }
                $item->oper_info = DataCacheService::getOperDetail($item->oper_id);//运营中心客服电话

                // 补充商户信息
                if($item->merchant_type == Order::MERCHANT_TYPE_SUPERMARKET){//超市
                    $csMerchant = CsMerchant::where('id',$item->merchant_id)->first();
                    $csMerchantSetting = CsMerchantSettingService::getDeliverSetting($csMerchant->id);
                    $csMerchant->delivery_start_price = $csMerchantSetting->delivery_start_price;
                    $csMerchant->delivery_charges = $csMerchantSetting->delivery_charges;
                    $csMerchant->delivery_free_start = $csMerchantSetting->delivery_free_start;
                    $csMerchant->delivery_free_order_amount = $csMerchantSetting->delivery_free_order_amount;
                    $csMerchant->city_limit = SettingService::getValueByKey('supermarket_city_limit');
                    $csMerchant->show_city_limit = SettingService::getValueByKey('supermarket_show_city_limit');
                    $item->cs_merchant = $csMerchant;
                }else {
                    $item->merchant = Merchant::where('id', $item->merchant_id)->first();
                    $item->merchant_logo = $item->merchant->logo;
                    $item->signboard_name = $item->merchant->signboard_name;
                }
                $item->deliver_price -= $item->discount_price;
            } else {
                // 注入普通商户信息
                $item->items = OrderItem::where('order_id', $item->id)->get();
                // 判断商户是否是当前小程序关联运营中心下的商户
                $item->isOperSelf = $item->oper_id === $currentOperId ? 1 : 0;
                $item->goods_end_date = Goods::withTrashed()->where('id', $item->goods_id)->value('end_date');
                $item->merchant = Merchant::where('id', $item->merchant_id)->first();
                $item->merchant_logo = $item->merchant->logo;
                $item->signboard_name = $item->merchant->signboard_name;
                $item->ybk_anchor_nickname = '';
                if ($item->type == Order::TYPE_DISHES) {
                    $item->dishes_items = DishesItem::where('dishes_id', $item->dishes_id)->get();
                }
            }
            $groupBookOrder = MarketingApi::getGroupBookOrderById($item->pintuan_order_id);
            $groupBookOrderTitle = [
                GroupBookController::ORDER_STATUS_NOT_OPEN => '未开团',
                GroupBookController::ORDER_STATUS_OPENING => '拼团中',
                GroupBookController::ORDER_STATUS_SUCCESS => '拼团成功',
                GroupBookController::ORDER_STATUS_FAIL => '拼团失败',
            ];
            if($groupBookOrder) {
                $groupBookOrder['title'] = $groupBookOrderTitle[$groupBookOrder['status']];
            }
            $item->group_book_order = $groupBookOrder;
            $marketingGoodsDetail = MarketingApi::getMarketingGoodsById($item->marketing_goods_id);
            // 添加拼团商品信息
            if ($marketingGoodsDetail) {
                $item->marketing_goods_datil = $marketingGoodsDetail;
            }
        });
        return $data;
    }

    /**
     * @param $orderId
     * @return \Illuminate\Http\JsonResponse|mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public static function manualRefund($orderId)
    {
        $order = OrderService::getById($orderId);
        $payment = Payment::findOrFail($order->pay_type);
        switch ($payment->type){
            case Payment::TYPE_WECHAT:
                $payment = new WechatPay();
                break;
            case Payment::TYPE_WALLET:
                $payment = new WalletPay();
                break;
            case Payment::TYPE_ALIPAY:
                $payment = new AliPay();
                break;
            default:
                throw new BaseResponseException('尚未有该退款流程');
        }
        return $payment->refund($order);
    }

    /**
     * @param $pintuanOrderId
     * @return Order[]|Collection
     */
    public static function getListByPinTuanOrderId($pintuanOrderId)
    {
        $list = Order::where('pintuan_order_id', $pintuanOrderId)->get();

        return $list;
    }

    public static function getGroupBookCreatorOrder($pintuanOrderId)
    {
        return Order::where('pintuan_order_id', $pintuanOrderId)
            ->orderBy('id', 'asc')->first();
    }

    public static function setGroupBookOrderSuccess($pintuanOrderId)
    {
        Order::where('pintuan_order_id', $pintuanOrderId)
            ->whereIn('status', [Order::STATUS_PAID_UNSETTLED])
            ->each(function($order){
                $order->status = Order::STATUS_PAID;
                if ($order->merchant_type == Order::MERCHANT_TYPE_SUPERMARKET) {
                    $order->status = ($order->deliver_type == Order::DELIVERY_MERCHANT_POST) ? Order::STATUS_UNDELIVERED : Order::STATUS_NOT_TAKE_BY_SELF;
                }
                $order->save();
                if ($order->type == Order::TYPE_GROUP_BUY) {
                    self::makeVerifyCode($order);
                    SmsVerifyCodeService::sendBuySuccessNotify($order->order_no);
                }
        });
        return true;
    }

    public static function getCountGroupBookOrder()
    {
        return Order::where('pintuan_order_id', '!=', 0)->count();
    }

}
