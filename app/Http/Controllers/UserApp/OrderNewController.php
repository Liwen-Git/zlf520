<?php

namespace App\Http\Controllers\UserApp;

use App\Exceptions\BaseResponseException;
use App\Exceptions\DataNotFoundException;
use App\Exceptions\ParamInvalidException;
use App\Modules\Ali\AliService;
use App\Modules\Cs\CsGood;
use App\Modules\Cs\CsMerchant;
use App\Modules\Cs\CsMerchantSettingService;
use App\Modules\Cs\CsUserAddress;
use App\Modules\CsOrder\CsOrderGood;
use App\Modules\Dishes\Dishes;
use App\Modules\Dishes\DishesGoods;
use App\Modules\Dishes\DishesItem;
use App\Modules\FeeSplitting\FeeSplittingRecord;
use App\Modules\FeeSplitting\FeeSplittingService;
use App\Modules\Goods\Goods;
use App\Modules\Merchant\Merchant;
use App\Modules\Merchant\MerchantService;
use App\Modules\Merchant\MerchantSettingService;
use App\Modules\Oper\Oper;
use App\Modules\Order\Order;
use App\Modules\Order\OrderService;
use App\Modules\Payment\Payment;
use App\Modules\Payment\PaymentService;
use App\Modules\Wechat\WechatService;
use App\Result;
use App\ResultCode;
use App\Support\Payment\AliPay;
use App\Support\Payment\WechatPay;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 新订单接口
 * Class OrderNewController
 * @package App\Http\Controllers\UserApp
 */
class OrderNewController extends Controller
{

    /**
     * 下单检查商户状态
     * @param CsMerchant | Merchant $merchant
     * @return bool
     */
    private function checkMerchantStatus($merchant)
    {
        $auditStatusSuccess = ($merchant instanceof Merchant) ? Merchant::AUDIT_STATUS_SUCCESS : CsMerchant::AUDIT_STATUS_SUCCESS;
        $auditStatusReSubmit = ($merchant instanceof Merchant) ? Merchant::AUDIT_STATUS_RESUBMIT : CsMerchant::AUDIT_STATUS_RESUBMIT;
        $statusOff = ($merchant instanceof Merchant) ? Merchant::STATUS_OFF : CsMerchant::STATUS_OFF;
        if (($merchant->audit_status != $auditStatusSuccess && $merchant->audit_status != $auditStatusReSubmit)
            || ($merchant->status == $statusOff)) {
            throw new BaseResponseException('商家异常，请联系商家');
        }
        return true;
    }

    /**
     * 团购下单
     */
    public function groupBuy()
    {
        $this->validate(request(), [
            'goods_id' => 'required|integer|min:1',
            'number' => 'integer|min:1',
            'verify_total_price' => 'numeric|min:0',
        ]);
        $goodsId = request('goods_id');
        $number = request('number', 1);
        $goods = Goods::findOrFail($goodsId);
        $payPrice = $goods->price * $number;
        $this->orderVerify(request('verify_total_price'),$payPrice);
        $user = request()->get('current_user');
        $merchant = MerchantService::getById($goods->merchant_id);
        $oper = $this->checkOperLegal($merchant->oper_id);

        $order = OrderService::makeOrderBasic($user, $merchant);
        $order->pay_target_type = $oper->pay_to_platform ? Order::PAY_TARGET_TYPE_PLATFORM : Order::PAY_TARGET_TYPE_OPER;
        $order->goods_id = $goodsId;
        $order->goods_name = $goods->name;
        $order->goods_pic = $goods->pic;
        $order->goods_thumb_url = $goods->thumb_url;
        $order->price = $goods->price;
        $order->buy_number = $number;
        $order->pay_price = $payPrice;
        $order->origin_app_type = request()->header('app-type');
        $order->remark = request('remark', '');
        $order->save();
        return Result::success($order);
    }

    /**
     * 直接支付到商户  对应之前scanQRCodePay
     */
    public function moneyBuy()
    {
        $this->validate(request(), [
            'merchant_id' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);
        $price = request('price');
        if ($price <= 0) {
            throw new ParamInvalidException('价格不合法');
        }
        $user = request()->get('current_user');
        $merchant = MerchantService::getById(request('merchant_id'));
        if (empty($merchant)) {
            throw new DataNotFoundException('商户信息不存在！');
        }
        $this->checkMerchantStatus($merchant);
        $this->checkOperLegal($merchant->oper_id);

        $order = OrderService::makeOrderBasic($user, $merchant);
        $order->type = Order::TYPE_SCAN_QRCODE_PAY;
        $order->goods_id = 0;
        $order->goods_name = $merchant->name;
        $order->goods_pic = $merchant->logo;
        $order->price = $price;
        $order->pay_price = $price;
        $order->remark = request('remark', '');
        $order->pay_target_type = Order::PAY_TARGET_TYPE_PLATFORM;
        $order->origin_app_type = request()->header('app-type');
        $order->save();
        return Result::success($order);
    }

    /**
     * 超市订单
     */
    public function supermarketBuy()
    {
        //必传商家id，支付方式，商品列表 配送方式
        $this->validate(request(), [
            'merchant_id' => 'required|integer|min:1',
            'goods_list' => 'required|min:1',
            'delivery_type' => 'required|integer|min:1',
            'verify_total_price' => 'numeric|min:0',
        ]);
        $merchantId = request('merchant_id');
        $addressId = request('address_id');
        $deliveryType = request('delivery_type', 0);
        $remark = request('remark', '');
        $goodsList = request('goods_list');
        //如果是商家配送必须选择收货地址
        $address = '';

        if ($deliveryType == Order::DELIVERY_MERCHANT_POST) {
            if (empty($addressId)) {
                throw new ParamInvalidException('请先选择地址');
            }
            $address = CsUserAddress::find($addressId);
            if (empty($address)) {
                throw new ParamInvalidException('收货地址不存在');
            }

        }
        $merchant = CsMerchant::find($merchantId);
        if (empty($merchant)) {
            throw new BaseResponseException('该超市不存在，请选择其他超市下单', ResultCode::CS_MERCHANT_NOT_EXIST);
        }
        if ($merchant->status != CsMerchant::STATUS_ON || $merchant->audit_status != CsMerchant::AUDIT_STATUS_SUCCESS) {
            throw new BaseResponseException('该超市状态异常，请选择其他超市下单', ResultCode::CS_MERCHANT_NOT_EXIST);
        }
        if (is_string($goodsList)) {
            $goodsList = json_decode($goodsList, true);
        }

        $goodsPrice = OrderService::checkGoodsStockAndReturnPrice($merchant, $goodsList, 1);
        $oper = $this->checkOperLegal($merchant->oper_id);
        $csMerchantSetting = CsMerchantSettingService::getDeliverSetting($merchantId);

        // 商家配送必须达到 起送价
        if (
            $deliveryType == Order::DELIVERY_MERCHANT_POST
            && $goodsPrice < $csMerchantSetting->delivery_start_price
        ) {
            throw new BaseResponseException('商品价格小于起送价');
        }
        $discountPrice = 0;
        $deliverPrice = 0; // 运费
        $totalPrice = $goodsPrice;
        $payPrice = $goodsPrice;
        if ($deliveryType == Order::DELIVERY_MERCHANT_POST) { // 商家配送, 有配送费
            $deliverPrice = $csMerchantSetting->delivery_charges; // 运费
            if ($csMerchantSetting->delivery_free_start && $goodsPrice >= $csMerchantSetting->delivery_free_order_amount) {
                $discountPrice = $csMerchantSetting->delivery_charges;
            }
            $payPrice = $totalPrice + $deliverPrice - $discountPrice;
        }

        $this->orderVerify(request('verify_total_price'),$payPrice);

        //创建订单
        $user = request()->get('current_user');
        $merchant->bizer_id = 0;
        $order = OrderService::makeOrderBasic($user, $merchant);
        $order->merchant_type = Order::MERCHANT_TYPE_SUPERMARKET;
        $order->type = Order::TYPE_SUPERMARKET;
        $order->goods_name = $merchant->signboard_name ?? '';
        $order->dishes_id = 0;
        $order->deliver_price = $deliverPrice;
        $order->total_price = $totalPrice;
        $order->discount_price = $discountPrice;
        $order->pay_price = $payPrice;
        $order->remark = $remark;
        $order->pay_target_type = $oper->pay_to_platform ? Order::PAY_TARGET_TYPE_PLATFORM : Order::PAY_TARGET_TYPE_OPER;
        $order->pay_type = 0;       // 取消pay_type
        $order->settlement_rate = $merchant->settlement_rate;
        $order->origin_app_type = request()->header('app-type');
        $order->deliver_type = $deliveryType;
        $order->express_address = $address ? json_encode($address) : $address;
        $order->delivery_start_price = $csMerchantSetting->delivery_start_price;
        $order->delivery_charges = $csMerchantSetting->delivery_charges;
        $order->delivery_free_start = $csMerchantSetting->delivery_free_start;
        $order->delivery_free_order_amount = $csMerchantSetting->delivery_free_order_amount;

        DB::beginTransaction();
        try {
            $order->save();
            //更新商品库存销量
            foreach ($goodsList as $item) {
                $good = CsGood::findOrFail($item['id']);
                $good->sale_num += $item['number'];
                $good->stock -= $item['number'];
                $good->save();

                $csOrderGood = new CsOrderGood();
                $csOrderGood->oper_id = $merchant->oper_id;
                $csOrderGood->price = $good->price;
                $csOrderGood->goods_name = $good->goods_name;
                $csOrderGood->cs_merchant_id = $merchant->id;
                $csOrderGood->cs_goods_id = $good->id;
                $csOrderGood->number = $item['number'];
                $csOrderGood->order_id = $order->id;
                $csOrderGood->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('超市下单失败', [
                'message' => $e->getMessage(),
                'data' => $e
            ]);
            throw new BaseResponseException('系统错误，创建订单失败');
        }
        return Result::success($order);
    }

    /**
     * 单品下单 对应旧的 dishesBuy
     */
    public function singleBuy()
    {
        $this->validate(request(), [
            'dishes_id' => 'required|integer|min:1',
            'verify_total_price' => 'numeric|min:0',
        ]);
        $dishesId = request('dishes_id');
        $payPrice = $this->getTotalPrice($dishesId);
        $this->orderVerify(request('verify_total_price'),$payPrice);
        $dishes = Dishes::findOrFail($dishesId);
        $user = request()->get('current_user');
        $merchant = MerchantService::getById($dishes->merchant_id);
        $this->checkMerchantStatus($merchant);
        $oper = $this->checkOperLegal($merchant->oper_id);
        if ($dishes->user_id != $user->id) {
            throw new ParamInvalidException('参数错误');
        }
        if (!MerchantSettingService::getValueByKey($dishes->merchant_id, 'dishes_enabled')) {
            throw new BaseResponseException('单品购买功能尚未开启！');
        }
        //判断商品上下架状态
        $dishesItems = DishesItem::where('dishes_id', $dishesId)
            ->where('user_id', $dishes->user_id)
            ->get();
        foreach ($dishesItems as $item) {
            $dishesGoods = DishesGoods::findOrFail($item->dishes_goods_id);
            if ($dishesGoods->status == DishesGoods::STATUS_OFF) {
                throw new BaseResponseException('菜单已变更, 请刷新页面');
            }
        }

        $order = OrderService::makeOrderBasic($user, $merchant);
        $order->goods_name = $merchant->name ?? '';
        $order->type = Order::TYPE_DISHES;
        $order->dishes_id = $dishesId;
        $order->pay_price = $payPrice;
        $order->remark = request('remark', '');
        $order->pay_target_type = $oper->pay_to_platform ? Order::PAY_TARGET_TYPE_PLATFORM : Order::PAY_TARGET_TYPE_OPER;

        $order->origin_app_type = request()->header('app-type');
        $order->save();
        return Result::success($order);
    }

    /**
     * 验证运营中心合法性
     * @param $operId
     * @return Oper
     */
    private function checkOperLegal($operId)
    {
        $oper = Oper::find($operId);
        if (empty($oper)) {
            throw new DataNotFoundException('该商户的运营中心不存在！');
        }
        if ($oper->pay_to_platform == Oper::PAY_TO_OPER) {
            throw new BaseResponseException('该商品不能在APP下单, 请在小程序下单');
        }
        return $oper;
    }

    /**
     * 订单退款
     */
    public function refund()
    {
        $this->validate(request(), [
            'order_no' => 'required'
        ]);
        $order = OrderService::getInfoByOrderNo(request()->get('order_no'));
        $payment = PaymentService::getDetailById($order->pay_type);
        if($payment->type==Payment::TYPE_WECHAT){
            // 如果为微信支付,则返回支付参数
            $m = new WechatPay();
            $res =  $m->refund($order);
        }elseif ($payment->type==Payment::TYPE_ALIPAY){
            $m = new AliPay();
            $res = $m->refund($order);
        }else{
            $paymentClassName = '\\App\\Support\\Payment\\'.$payment->class_name;
            if(!class_exists($paymentClassName)){
                throw new BaseResponseException('无法使用该退款方式');
            }
            $paymentClass = new $paymentClassName();
            $res =  $paymentClass->refund($order);
        }
        // 还原库存
        OrderService::decSellNumber($order);
        return $res;
    }

    /**
     * 获取总价格
     * @param $dishesId
     * @return float|int
     */
    public function getTotalPrice($dishesId)
    {
        $list = DishesItem::where('dishes_id', $dishesId)->get();
        $totalPrice = 0;
        foreach ($list as $v) {
            $totalPrice += ($v->dishes_goods_sale_price) * ($v->number);
        }

        return $totalPrice;

    }

    public function pay(Request $request)
    {
        $this->validate($request, [
            'order_no' => 'required',
            'pay_type' => 'required|integer|min:1',

        ]);
        $orderNo = request('order_no');
        $order = Order::where('order_no', $orderNo)->first();
        $this->checkOrderCanPay($order);
        $payType = request('pay_type', Payment::ID_WECHAT);
        $order->pay_type = $payType;
        $order->save();
        $profitAmount = 0;
        if ($order->type == Order::TYPE_SCAN_QRCODE_PAY) {
            //返利金额
            $feeSplittingRecords = FeeSplittingService::getFeeSplittingRecordByOrderId($order->id, FeeSplittingRecord::TYPE_TO_SELF);
            if (!empty($feeSplittingRecords)) {
                $profitAmount = $feeSplittingRecords->amount;
            }
        }

        $sdkConfig = null;
        $data = null;
        $orderSignString = null;
        $payment = PaymentService::getDetailById($order->pay_type);
        if ($payment->type == Payment::TYPE_WECHAT) {
            // 如果为微信支付,则返回支付参数
            $sdkConfig = $this->_wechatPayToPlatform($order);
        } elseif ($payment->type == Payment::TYPE_ALIPAY) {
            // 支付宝支付返回参数
            $orderSignString = $this->_aliPay($order);
        } else {
            $paymentClassName = '\\App\\Support\\Payment\\' . $payment->class_name;
            if (!class_exists($paymentClassName)) {
                throw new BaseResponseException('无法使用该支付方式');
            }
            $user = request()->get('current_user');
            $paymentClass = new $paymentClassName();
            $data = $paymentClass->buy($user, $order);
        }

        $list = [
            'order_no' => $order->order_no,
            'sdk_config' => $sdkConfig,
            'order_sign_string' =>  $orderSignString,
            'order' => $order,
            'pay_type' => $order->pay_type,
            'data' => $data
        ];
        //判断是否需要返利金额
        if ($profitAmount > 0) {
            $profitAmounArr = ['profitAmount' => $profitAmount];
            $list = array_merge($list, $profitAmounArr);
        }
        return Result::success($list);
    }

    private function checkOrderCanPay($order)
    {
        if ($order->merchant_type == Order::MERCHANT_TYPE_NORMAL) {
            $merchant = MerchantService::getById($order->merchant_id);
            $this->checkMerchantStatus($merchant);
        } else {
            $merchant = CsMerchant::find($order->merchant_id);
            if (empty($merchant)) {
                throw new BaseResponseException('该超市不存在，请选择其他超市下单', ResultCode::CS_MERCHANT_NOT_EXIST);
            }
        }
        if ($order->status == Order::STATUS_PAID) {
            throw new ParamInvalidException('该订单已支付');
        }
        if ($order->status == Order::STATUS_CANCEL) {
            throw new ParamInvalidException('该订单已取消');
        }
        if ($order->status != Order::STATUS_UN_PAY) {
            throw new BaseResponseException('订单状态异常');
        }
        if ($order->pay_target_type != Order::PAY_TARGET_TYPE_PLATFORM) {
            throw new BaseResponseException('该订单不能在APP中支付, 请到小程序中付款');
        }
    }

    /**
     * 订单支付到平台, 返回微信支付参数
     * @param $order
     * @return null|array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    private function _wechatPayToPlatform(Order $order)
    {
        $sdkConfig = null;
        $payApp = WechatService::getOpenPlatformPayAppFromPlatform();
        $data = [
            'body' => $order->merchant_name,
            'out_trade_no' => $order->order_no,
            'total_fee' => bcmul($order->pay_price, 100),
            'trade_type' => 'APP',
        ];
        $unifyResult = $payApp->order->unify($data);
        if (!($unifyResult['return_code'] === 'SUCCESS' && array_get($unifyResult, 'result_code') === 'SUCCESS')) {
            Log::error('微信统一下单失败', [
                'payConfig' => $payApp->getConfig(),
                'data' => $data,
                'result' => $unifyResult,
            ]);
            throw new BaseResponseException('微信统一下单失败');
        }
        $sdkConfig = $payApp->jssdk->appConfig($unifyResult['prepay_id']);
        $sdkConfig['packageValue'] = $sdkConfig['package'];
        return $sdkConfig;
    }

    /**
     * 支付宝支付
     * @param Order $order
     * @return mixed
     */
    private function _aliPay(Order $order)
    {
        $aliPay = new Alipay();
        $data = [
            'subject' => $order->merchant_name,
            'out_trade_no' => $order->order_no,
            'total_amount' => $order->pay_price,
        ];
        $res = $aliPay->setConfig(AliService::getAliPayApp())->getApp($data);
        return $res;
    }

    /**
     * 校验订单信息
     * @param $postPrice
     * @param $phpPrice
     */
    private function orderVerify($postPrice, $phpPrice)
    {
        if($postPrice!=$phpPrice){
            throw new BaseResponseException('下单金额不一致');
        }
    }




}
