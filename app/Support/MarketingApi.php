<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/7
 * Time: 15:15
 */

namespace App\Support;


use App\Exceptions\BaseResponseException;
use App\Modules\Order\Order;
use App\ResultCode;
use App\Support\Curl;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

use App\Modules\Order\OrderService;


class MarketingApi
{
    const GOODS_LIST_START_URL = '/internal_api/marketing_goods/getGoodsListStart';
    const GOODS_LIST_SOON_URL = '/internal_api/marketing_goods/getGoodsListSoon';
    const MARKETING_URL = '/internal_api/marketing_goods/getMarketing';
    const MARKETING_INFO_URL = '/internal_api/marketing/getMarketingInfo';
    const MERCHANT_MARKETING_URL = '/internal_api/marketing_merchant/getMarketingListStarted';
    const USER_VIEW_URL = '/internal_api/marketing/userView';
    const ADD_MARKETING_INFO_URL = '/internal_api/marketing_goods/addMarketingInfo';
    const MERCHANT_GOODSLIST_START_URL = '/internal_api/marketing_goods/getMerchantGoodsListStart';
    const MARKETING_GOODS_IN_STATUS_URL = '/internal_api/marketing_goods/getMarketingGoodsInStatus';
    const CAN_DISCOUNT_GOODS_FROM_MARKETING_URL = '/internal_api/marketing_goods/getCanDiscountGoods';
    const FORM_RECORD_ORDER_TO_MARKETING_URL = '/internal_api/marketing_sales/getInform';
    const CHANGE_ORDER_STATUS_TO_MARKETING = '/internal_api/marketing_sales/updateStatus';
    const DURING_PHASE_GOOD_FROM_MARKETING_URL = '/internal_api/marketing_goods/getDuringPhaseGood';
    const GROUP_BOOK_GOODS_URL = '/internal_api/marketing_goods/getGroupBookGoods';
    const GROUP_BOOK_SINGLE_GOODS_URL = '/internal_api/marketing_goods/getGroupBookGoodsByIds';
    const LOCK_GOODS_URL = '/internal_api/marketing_goods/lockGoods';
    const GROUP_BOOK_CREATE_NEW_ORDER = '/internal_api/marketing_orders/makeNewGroupBookOrder';
    const GROUP_BOOK_GET_ORDER_BY_ID = '/internal_api/marketing_orders/getGroupBookOrderById';
    const GROUP_BOOK_ADD_GROUP_BOOK_ORDER_RECORD = '/internal_api/marketing_orders/addGroupBookOrderRecord';
    const PIN_TUAN_ORDER_DETAIL_URL = '/internal_api/marketing_orders/getPinTuanOrderDetail';
    const GROUP_BOOK_GET_USER_ORDERS_BY_STATUS = '/internal_api/marketing_orders/getUserOrdersByStatus';
    const GET_GROUP_BOOK_BY_TEAM_ID = '/internal_api/marketing_goods/getGroupBookGoodsByTeamId';

    const GET_MARKETING_GOODS_BY_ID = '/internal_api/marketing_goods/getById';
    const MARKETING_GROUP_BOOK = 2;
    const GET_IN_ACTIVITY_MARKETING_GOODS = '/internal_api/marketing_goods/getInActivityGoods';

    // 商品分类（1团购商品 2单品商品 3超市商品）
    const MARKETING_GOODS_TYPE_GOODS = 1;
    const MARKETING_GOODS_TYPE_DISHES = 2;
    const MARKETING_GOODS_TYPE_CS_GOODS = 3;

    public static function get($url, $data)
    {
        if(env('APP_ENV') == 'production'){
            $domain = 'http://' . env('MARKETING_IP');
        }else {
            $domain = config('common.marketing_domain');
        }
        $url = $domain.$url;
        $client = new Client();
        $response = $client->get($url, [
            'query' => $data
        ]);
        if($response->getStatusCode() !== 200){
            Log::error('营销系统网络请求失败', compact('url', 'data'));
            throw new BaseResponseException("网络请求失败");
        }
        $result = $response->getBody()->getContents();
        $array = is_string($result) ? json_decode($result, 1) : $result;
        return $array;
    }

    public static function post($url, $data)
    {
        if(env('APP_ENV') == 'production'){
            $domain = 'http://' . env('MARKETING_IP');
        }else {
            $domain = config('common.marketing_domain');
        }
        $url = $domain.$url;
        $client = new Client();
        $response = $client->post($url, [
            'form_params' => $data
        ]);
        if($response->getStatusCode() !== 200){
            Log::error('营销系统网络请求失败', compact('url', 'data'));
            throw new BaseResponseException("网络请求失败");
        }
        $result = $response->getBody()->getContents();
        $array = is_string($result) ? json_decode($result,1 ) : $result;
        return $array;
    }

    /**
     * 获取开始中商品
     * @param $type 商品类型： 1 超市 2 本地
     * @param $city_id 城市编号
     * @param $page 页码
     * @param $pageSize 分页大小
     * @return array || null
     */
    public static function getGoodsListStart($type,$city_id,$page,$pageSize)
    {
        $params['type'] = $type;
        $params['city_id'] = $city_id;
        $params['page'] = $page;
        $params['pageSize'] = $pageSize;
        $data = self::get(self::GOODS_LIST_START_URL,$params);
        return $data['data']?? null;
    }

    /**
     * 获取即将开始商品
     * @param $type 商品类型： 1 超市 2 本地
     * @param $city_id 城市编号
     * @param $page 页码
     * @param $pageSize 分页大小
     * @return array || null
     */
    public static function getGoodsListSoon($type,$city_id,$page,$pageSize)
    {
        $params['type'] = $type;
        $params['city_id'] = $city_id;
        $params['page'] = $page;
        $params['pageSize'] = $pageSize;
        $data = self::get(self::GOODS_LIST_SOON_URL,$params);
        return $data['data']?? null;
    }

    /**
     * 获取活动信息
     * @return array || null
     */
    public static function getMarketing()
    {
        $data = self::get(self::MARKETING_URL,[]);
        return $data['data']?? null;
    }

    /**
     * 获取商户活动信息
     * @param $merchant_id 商户编号
     * @return array || null
     */
    public static function getMerchantMarketing($merchant_id)
    {
        $params['merchant_id'] = $merchant_id;
        $data = self::get(self::MERCHANT_MARKETING_URL,$params);
        return $data['data']?? null;
    }

    /**
     * 获得开始商户商品列表
     * @param $params
     * @return array || null
     */
    public static function getMerchantGoodsListStart($params)
    {
        $data = self::get(self::MERCHANT_GOODSLIST_START_URL,$params);
        return $data['data']?? null;
    }

    /**
     * 补全营销系统信息
     * @param $merchant_id 商户id
     * @param $goods_type 商品类型： 1 超市 2 本地
     * @param $goods_ids 商品编号
     * @return array || null
     */
    public static function addMarketingInfo($merchant_id,$goods_type,$goods_ids)
    {
        $userId = request()->get('current_user')->id??0;
        $params['merchant_id'] = $merchant_id;
        $params['goods_type'] = $goods_type;
        $params['goods_ids'] = $goods_ids;
        $params['user_id'] = $userId;
        $data = self::get(self::ADD_MARKETING_INFO_URL,$params);
        return $data['data']?? null;
    }

    /**
     * 用户查看
     * @param $marketing_id 活动编号
     * @param $phase_id 期号编号
     * @param $current_device_no
     * @return bool|null
     */
    public static function userView($marketing_id, $phase_id, $current_device_no)
    {
        if (empty($marketing_id) || empty($phase_id) || empty($current_device_no)) {
            return false;
        }

        $set_key = 'marketing:uer_view:' . $marketing_id . '_' . $phase_id;

        $exist = Redis::sismember($set_key,$current_device_no);

        if (!$exist) {
            $rs = Redis::sadd($set_key,$current_device_no);
            $params['marketing_id'] = $marketing_id;
            $params['phase_id'] = $phase_id;
            $data = self::get(self::USER_VIEW_URL,$params);
            return $data['data']?? null;
        } else {
            return true;
        }

    }

    /**
     * 获取活动信息
     * @param $marketing_id int 编号
     * @return null
     */
    public static function getMarketingInfo($marketing_id)
    {
        $params['marketing_id'] = $marketing_id;
        $data = self::get(self::MARKETING_INFO_URL,$params);
        return $data['data']?? null;
    }

    /**
     * 获取商品状态是 待审核 和 审核通过 的商品列表
     * @param $goodsId
     * @param $goodsType
     * @return bool|array
     */
    public static function getMarketingGoodsInStatus($goodsId, $goodsType)
    {
        $params = [
            'goods_id'   =>  $goodsId,
            'goods_type'   =>  $goodsType,
        ];
        $data = self::post(self::MARKETING_GOODS_IN_STATUS_URL,$params);
        return $data['data']?? false;
    }


    /**
     * 从营销平台 获取可优惠活动商品
     * @param $merchantId
     * @param $goodsIds
     * @param $phaseId
     * @param $marketingId
     * @param $goodsType
     * @param $userId
     * @return bool|array
     */
    public static function getCanDiscountGoodsFromMarketing($merchantId,Array $goodsIds,$phaseId,$marketingId,$goodsType,$userId)
    {
        $params = [
            'merchant_id'   =>  $merchantId,
            'goods_ids'     =>  implode(',',$goodsIds),
            'phase_id'      =>  $phaseId,
            'marketing_id'  =>  $marketingId,
            'goods_type'    =>  $goodsType,
            'user_id'       =>  $userId,
        ];
        $result = self::get(self::CAN_DISCOUNT_GOODS_FROM_MARKETING_URL,$params);
        if(isset($result['code']) && $result['code'] !== 0){
            if($result['code'] == 50001){
                throw new BaseResponseException('活动已结束');
            }else if($result['code'] == 50002){
                throw new BaseResponseException('活动尚未开始');
            }
            throw new BaseResponseException('请求活动系统失败');
        }
        return $result['data']?? false;
    }

    /**
     * 通知营销端处理下单逻辑
     * @param $order
     * @param $orderGoods
     * @return bool
     */
    public static function informRecordOrderToMarketing($order)
    {
        if($order->phase_id==0 || $order->marketing_id==0){
            return true;
        }
        $orderGoods = OrderService::getPhaseGoodsList($order);
        $params = [
            'order_info'   =>  json_encode($order),
            'goods_info'   =>  json_encode($orderGoods),
        ];
        Log::error(print_r($params,true));
        if(empty($orderGoods)){
            return true;
        }
        $data = self::post(self::FORM_RECORD_ORDER_TO_MARKETING_URL,$params);
        return $data['data']?? false;
    }

    public static function informRecordOrderToMarketingGroupBook($order)
    {
        if($order->marketing_id!=MarketingApi::MARKETING_GROUP_BOOK){
            return true;
        }
        $orderGoods = OrderService::getPhaseGoodsList($order);
        $params = [
            'order_info'   =>  json_encode($order),
            'goods_info'   =>  json_encode($orderGoods),
        ];
        $data = self::post(self::GROUP_BOOK_ADD_GROUP_BOOK_ORDER_RECORD,$params);
        if ( in_array($data['code'], [ResultCode::RULE_NOT_FOUND, ResultCode::AUTH_GROUP_NOT_FOUND]) ) {
            $errorMessage = (ResultCode::RULE_NOT_FOUND) ? '该商品已满，不可开新团' : '该团已满，不可再拼';
            Log::error($errorMessage, $params);
            return ['is_refund'=>'refund'];
        }

        // 同步活动订单销售信息
        $res = self::post(self::FORM_RECORD_ORDER_TO_MARKETING_URL,$params);
        Log::info('同步活动订单销售信息结果', $res);
        return $data['data']?? false;
    }

    /**
     * 通知营销端处理订单状态修改
     * @param $orderId
     * @param $status
     * @return bool
     */
    public static function informChangeOrderStatusToMarketing($orderId,$status)
    {
        $params = [
            'order_id'   =>  $orderId,
            'status'   =>  $status,
        ];
        $data = self::post(self::CHANGE_ORDER_STATUS_TO_MARKETING,$params);
        return $data['data']?? false;
    }

    /**
     * 获取业务端所需参加活动的信息，用于判断是否显示抢购海报图倒计时效果
     * @param $marketingGoodParam
     * @return bool
     */
    public static function getDuringPhaseGoodFromMarketing($marketingGoodParam)
    {
        $data = self::get(self::DURING_PHASE_GOOD_FROM_MARKETING_URL,$marketingGoodParam);
        return $data['data']?? false;
    }

    public static function getInActivityMarketingGoods($goodsId, $goodsType)
    {
        $params = [
            'goods_id' => $goodsId,
            'goods_type' => $goodsType,
        ];
        $data = self::get(self::GET_IN_ACTIVITY_MARKETING_GOODS, $params);

        return $data['data']?? false;
    }

    /**
     * 获取拼团商品
     * @param $goodsType
     * @param int $merchantType
     * @param int $merchantId
     * @return bool
     */
    public static function getGroupBookingFromMarketing($goodsType, $merchantType=0, $merchantId=0)
    {
        $params = [
            'goods_type' => $goodsType,
        ];
        if($merchantType && $merchantId){
            $params['merchant_type'] = $merchantType;
            $params['merchant_id'] = $merchantId;
        }
        $data = self::get(self::GROUP_BOOK_GOODS_URL, $params);
        return $data['data'] ?? false;
    }

    /**
     * 获取拼团商品
     * @param $goodsType
     * @param $goodsId
     * @return bool
     */
    public static function getGroupBookingGoodsFromMarketingById($goodsType, $goodsId)
    {
        $data = self::get(self::GROUP_BOOK_SINGLE_GOODS_URL, [
            'goods_type' => $goodsType,
            'goods_id' => $goodsId,
        ]);
        return $data['data'] ?? false;
    }

    public static function getGroupBookingGoodsFromMarketingByTeamId($teamId)
    {
        $data = self::get(self::GET_GROUP_BOOK_BY_TEAM_ID, [
            'team_id' => $teamId,
        ]);
        return $data['data'] ?? false;
    }

    /**
     * 锁定营销端参加活动的商品
     * @param $goodsId
     * @return bool
     */
    public static function lockGoods($goodsId,$goodsType,$msg='')
    {
        $params = [
            'goods_id' => $goodsId,
            'goods_type' => $goodsType,
        ];
        $data = self::get(self::LOCK_GOODS_URL,$params);
        if($data['code'] > 0){
            if(!empty($msg)){
                throw new BaseResponseException($msg);
            }else{
                throw new BaseResponseException($data['message']);
            }
        }
        return false;
    }

    /**
     * 获取新开团order_id
     * @param Order $order
     * @return bool
     */
    public static function gerGroupBookNewOrderId(Order $order)
    {
        $data = self::get(self::GROUP_BOOK_CREATE_NEW_ORDER, [
            'order_info' => json_encode($order),
        ]);
        return $data['data'] ?? false;
    }

    /**
     * 通过拼团ID，获取拼团数据
     * @param $groupBookId
     * @return bool
     */
    public static function getGroupBookOrderById($groupBookId)
    {
        $data = self::get(self::GROUP_BOOK_GET_ORDER_BY_ID, [
            'order_id' => $groupBookId,
        ]);
        return $data['data'] ?? false;
    }

    /**
     * 获取拼团订单详情(包括里面子订单)
     * @param $pintuanOrderId
     */
    public static function getPinTuanOrderDetail($pintuanOrderId)
    {
        $params = [
            'pintuan_order_id' => $pintuanOrderId,
        ];
        $data = self::get(self::PIN_TUAN_ORDER_DETAIL_URL,$params);
        if($data['code'] > 0){
            throw new BaseResponseException($data['message']);
        }
        return $data['data'];
    }

    public static function getMarketingGoodsById($marketingGoodsId)
    {
        $data = self::get(self::GET_MARKETING_GOODS_BY_ID, [
            'id' => $marketingGoodsId,
        ]);
        return $data['data'] ?? false;
    }

    public static function getUserOrdersByStatus($userId, $status)
    {
        $data = self::get(self::GROUP_BOOK_GET_USER_ORDERS_BY_STATUS, [
            'user_id' => $userId,
            'status' => $status
        ]);
        return $data['data'] ?? false;
    }
}
