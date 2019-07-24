<?php

namespace App\Http\Controllers\UserApp;

use App\Exceptions\BaseResponseException;
use App\Modules\Cs\CsGood;
use App\Modules\Cs\CsGoodService;
use App\Modules\Goods\Goods;
use App\Modules\Goods\GoodsService;
use App\Modules\Order\OrderService;
use App\Modules\Setting\SettingService;
use App\Result;
use App\Support\Lbs;
use App\Support\MarketingApi;
use App\Support\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;

class GroupBookController extends Controller
{
    const TYPE_GROUP_GOODS = 1;
    const TYPE_SUPERMARKET_GOODS = 2;

    const ORDER_STATUS_NOT_OPEN = 1;
    const ORDER_STATUS_OPENING = 2;
    const ORDER_STATUS_SUCCESS = 3;
    const ORDER_STATUS_FAIL    = 4;
    const ORDER_STATUS_OPEN_FAIL = 5;
    public function getGoodsList()
    {
        $groupBookInfo = MarketingApi::getMarketingInfo(MarketingApi::MARKETING_GROUP_BOOK);
        // 获取活动配置, 如果活动未开启, 直接返回空列表
        if(is_null($groupBookInfo) || empty($groupBookInfo['marketing']) || $groupBookInfo['marketing']['status'] != 1){
            return Result::success([
                'list' => [],
                'total' => 0,
                'user_join' => 0,
                'marketing_id' => MarketingApi::MARKETING_GROUP_BOOK,
                'phase_id' => 0,
                'group_book_info' => (is_null($groupBookInfo)||empty($groupBookInfo['marketing']))
                    ? [] :
                    [
                        'share_title' => $groupBookInfo['marketing']['share_title'] ?? '',
                        'share_img_url' => $groupBookInfo['marketing']['share_img_url'] ?? ''
                    ],
            ]);
        }
        $this->validate(request(),[
            'goods_type'    =>  'required|in:'.implode(',',
                    [
                        self::TYPE_GROUP_GOODS,
                        self::TYPE_SUPERMARKET_GOODS
                    ]),
        ],[
            'goods_type.required'=>  '商品类型不可为空',
            'goods_type.in'     =>  '商品类型不合法',
        ]);
        $goodsType = request('goods_type');
        $cityId = request('city_id');
        $lng = request('lng');
        $lat = request('lat');
        $page = request('page');
        $pageSize = request('page_size', 15);
        $merchantId = request('merchant_id');
        $currentDeviceNo = request()->header('current-device-no');
        switch ($goodsType) {
            case self::TYPE_GROUP_GOODS :
                $insertDistance = ($lng && $lat) ? GoodsService::insertDistance($currentDeviceNo, $lng, $lat):false;
                $data = GoodsService::getGroupBookGoods($merchantId, $insertDistance, $page, $pageSize);
                break;
            case self::TYPE_SUPERMARKET_GOODS:
                $supermarketOn = SettingService::getValueByKey('supermarket_on');
                $data = ($supermarketOn==1) ? CsGoodService::getGroupBookGoods($merchantId, $page, $pageSize) : [];
                break;
            default:
                throw new BaseResponseException('暂无该商品类型');
        }
        $list = $data['list']??[];
        $total = $data['total']??0;
        return Result::success([
            'list' => $list,
            'total' => $total,
            'user_join' => OrderService::getCountGroupBookOrder()+578,
            'marketing_id' => MarketingApi::MARKETING_GROUP_BOOK,
            'phase_id' => 0,
            'group_book_info' => (is_null($groupBookInfo)||empty($groupBookInfo['marketing']))
                ? [] :
                [
                    'share_title' => $groupBookInfo['marketing']['share_title'] ?? '',
                    'share_img_url' => $groupBookInfo['marketing']['share_img_url'] ?? ''
                ],
        ]);
    }

    public function getOrdersList()
    {
        $groupBookStatus = request('status',0);
        $legalStatus = [
            self::ORDER_STATUS_OPENING,
            self::ORDER_STATUS_SUCCESS,
            self::ORDER_STATUS_FAIL,
            self::ORDER_STATUS_OPEN_FAIL,
        ];
        $legalStatusStr = implode(',', $legalStatus);
        $this->validate(request(), [
            'status' => 'in:'.$legalStatusStr,
        ],[
            'status.in' => '订单状态不合法',
        ]);
        if ($groupBookStatus==self::ORDER_STATUS_FAIL || $groupBookStatus==self::ORDER_STATUS_OPEN_FAIL) {
            $groupBookStatus = implode(',', [
                self::ORDER_STATUS_FAIL,
                self::ORDER_STATUS_OPEN_FAIL,
            ]);
        }
        if (!$groupBookStatus) {
            $groupBookStatus = $legalStatusStr;
        }
        $lng = request('lng');
        $lat = request('lat');
        $user = request()->get('current_user');
        $currentOperId = request()->get('current_oper_id');
        $data = OrderService::getGroupBookList($user->id, $groupBookStatus, $currentOperId, $lng, $lat);
        return Result::success(['list'=>$data->items(), 'total'=>$data->total()]);
    }

}
