<?php

namespace App\Modules\Wechat;

use App\BaseModel;

/**
 * Class MiniprogramScene
 * @package App\Modules\Wechat
 *
 * @property int    oper_id
 * @property int    merchant_id
 * @property int    invite_channel_id
 * @property int    type
 * @property string page
 * @property string payload
 * @property string qrcode_url
 * @property int good_id
 * @property int order_id
 *
 */
class MiniprogramScene extends BaseModel
{
    // 订单支付页面(订单支付页面的路径暂时由小程序传参过来)
    const PAGE_ORDER_PAY = 'pages/order/info';
    // 邀请注册页面
    const PAGE_INVITE_REGISTER = 'pages/login/index';
    // 扫码支付页面
    const PAGE_PAY_SCAN = 'pages/order/addOfPrice';
    // 扫码支付（带价格）支付
    const PAGE_PAY_SCAN_WITH_PRICE = 'order/scanQrcodePay';
    //普通商户详情页面
    //团购商品详情
    //单品商品详情 pages/dishes/index?scene=101 pages/dishes/index?scene=101&good_id=11
    //超市 pages/supermarket/detail?scene=101&merchant_id=11 pages/supermarket/detail?scene=101&merchant_id=11&good_id=11

    const TYPE_PAY_BRIDGE = 1; // 小程序间支付跳转
    const TYPE_INVITE_CHANNEL = 2; // 邀请注册渠道
    const TYPE_PAY_SCAN = 3; // 扫码支付
    const TYPE_MERCHANT_DETAIL = 4;         //  普通商户详情页
    const TYPE_SUPERMARKET_DETAIL = 5;      //  超市商户详情页
    const TYPE_GROUP_BUY = 6;               //  团购商品页
    const TYPE_MERCHANT_GOOD = 7;           //  普通商品页
    const TYPE_SUPERMARKET_GOOD = 8;        //  超市商品页
    const TYPE_DISHES_GOOD = 9;             //  单品详情页
    const TYPE_PAY_SCAN_WITH_PRICE = 10; //扫码支付带价格
    const TYPE_GROUP_BOOK_TEAM = 11;        //  拼团邀请

    /**
     * 推广海报pages
     */
    const POSTER_PAGES = [
        self::TYPE_MERCHANT_DETAIL  =>  'pages/merchant/index',
        self::TYPE_SUPERMARKET_DETAIL  =>  'pages/supermarket/detail',
        self::TYPE_GROUP_BUY  =>  'pages/product/info',
        self::TYPE_MERCHANT_GOOD  =>  'pages/dishes/index',
        self::TYPE_SUPERMARKET_GOOD  =>  'pages/supermarket/detail',
        self::TYPE_DISHES_GOOD  =>  'pages/dishes/index',
        self::TYPE_GROUP_BOOK_TEAM => 'pages/group/order-detail',
    ];
}
