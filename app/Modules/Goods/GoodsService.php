<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/19
 * Time: 19:52
 */

namespace App\Modules\Goods;


use App\BaseService;
use App\Exceptions\BaseResponseException;
use App\Exceptions\DataNotFoundException;
use App\Exceptions\ParamInvalidException;
use App\ResultCode;
use App\Support\Lbs;
use App\Support\MarketingApi;
use App\Modules\FilterKeyword\FilterKeyword;
use App\Modules\FilterKeyword\FilterKeywordService;
use App\Modules\Merchant\Merchant;
use App\Modules\Merchant\MerchantService;
use App\Support\Curl;
use App\Support\Utils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GoodsService extends BaseService
{
    const GROUP_BOOKING_ON = 1;
    const GROUP_BOOKING_OFF= 0;

    /**
     * 获取商品列表
     * @param $merchantId
     * @param null $status
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getList($merchantId, $status = null)
    {
        $data = Goods::where('merchant_id', $merchantId)
            ->when($status, function (Builder $query) use ($status) {
                $query->where('status', $status);
            })->orderBy('sort', 'desc')->paginate();

        return $data;
    }

    public static function getGroupBookByIds($ids)
    {
        $data = MarketingApi::getGroupBookingGoodsFromMarketingById(1, $ids);
        $list = [];
        if ($data) {
            if (isset($data['id'])) {
                $list[$data['goods_id']] = $data;
            }else{
                foreach ($data as $k => $v) {
                    $list[$v['goods_id']] = $v;
                }
            }
        }
        return $list;
    }

    /**
     * 首页商户列表，显示价格最低的n个团购商品
     * @param int $merchantId 商户ID
     * @param int $number 要获取的商品个数
     * @return Goods[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getLowestPriceGoodsForMerchant($merchantId, $number = null)
    {
        $list = Goods::where('merchant_id', $merchantId)
            ->where('status', Goods::STATUS_ON)
            ->where('stock','>',0)
            ->orderBy('sort', 'desc');
        if($number){
            $list->limit($number);
        }
        $data = $list->get();
        if ($data) {
            $goods_ids = [];
            $data->each(function ($item) use (&$goods_ids){
                $goods_ids[] = $item->id;
                $item->pic_list_new = $item->pic_list;
                $item->pic_list_arr_new = $item->pic_list ? explode(',', $item->pic_list) : [];
                $item->marketing_started = (object) [];
                $item->marketing_soon = (object) [];
            });

            if ($goods_ids) {
                $goods_ids = implode(',',$goods_ids);
                $groupBookList = GoodsService::getGroupBookByIds($goods_ids);
                $marketing_info_ori = MarketingApi::addMarketingInfo($merchantId,1,$goods_ids);
                $marketing_info = $marketing_info_ori['list'];
                if (empty($marketing_info)) {
                    return $data;
                }
                $haveGroupBookGoods = false;
                $data= $data->each(function ($item) use (
                    $marketing_info,
                    $marketing_info_ori,
                    &$haveGroupBookGoods,
                    $groupBookList
                ) {
                    $item->is_marketing = 0;
                    if (!empty($marketing_info[$item->id]['marketing_started'])) {
                        $item->is_marketing = 1;
                        $item->marketing_started->user_limit = $marketing_info[$item->id]['marketing_started']['limited'];
                        $item->marketing_started->sales_count = $marketing_info[$item->id]['marketing_started']['sales_count'];
                        $item->marketing_started->marketing_stock = $marketing_info[$item->id]['marketing_started']['stock'];
                        $item->marketing_started->discount_price = $marketing_info[$item->id]['marketing_started']['discount_price'];
                        $item->marketing_started->marketing_id = $marketing_info[$item->id]['marketing_started']['marketing_id'];
                        $item->marketing_started->phase_id = $marketing_info[$item->id]['marketing_started']['phase_id'];
                        $phase = $marketing_info_ori['phase_started'];
                        $item->marketing_started->end_time_left = strtotime($phase['end_time']) - time();
                    }

                    if (!empty($marketing_info[$item->id]['marketing_soon'])) {
                        $item->marketing_soon->user_limit = $marketing_info[$item->id]['marketing_soon']['limited'];
                        $item->marketing_soon->sales_count = $marketing_info[$item->id]['marketing_soon']['sales_count'];
                        $item->marketing_soon->marketing_stock = $marketing_info[$item->id]['marketing_soon']['stock'];
                        $item->marketing_soon->discount_price = $marketing_info[$item->id]['marketing_soon']['discount_price'];
                        $item->marketing_soon->marketing_id = $marketing_info[$item->id]['marketing_soon']['marketing_id'];
                        $item->marketing_soon->phase_id = $marketing_info[$item->id]['marketing_soon']['phase_id'];
                        $phase = $marketing_info_ori['phase_soon'];
                        $item->marketing_soon->start_time_left = strtotime($phase['start_time']) - time();
                    }
                    GoodsService::insertGroupBookDetail($item, 1, $groupBookList[$item->id] ?? []);
                    if ($item->is_group_booking==GoodsService::GROUP_BOOKING_ON) {
                        $haveGroupBookGoods = true;
                    }
                });
                if ($haveGroupBookGoods) {
                    $data = $data->sortByDesc('is_group_booking')
                        ->values()
                        ->all();
                }

            }

        }
        return $data;
    }

    /**
     * 获取商品详情
     * @param int $id
     * @return Goods|null
     */
    public static function getById($id)
    {
        $goods = Goods::find($id);
        if(empty($goods)){
            return null;
        }
        return $goods;
    }

    /**
     * 根据ID及商户ID获取商品
     * @param $id
     * @param $merchantId
     * @return Goods|null
     */
    public static function getByIdAndMerchantId($id, $merchantId)
    {
        $goods = Goods::where('id', $id)->where('merchant_id', $merchantId)->first();
        if(empty($goods)){
            return null;
        }
        return $goods;
    }

    /**
     * 添加商品
     * @param $operId
     * @param $merchantId
     * @return Goods
     */
    public static function addFromRequest($operId, $merchantId)
    {
        $goods = new Goods();
        $marketPrice = request('market_price', 0);
        $price = request('price', 0);
        if($marketPrice < $price){
            throw new BaseResponseException('市场价不能小于销售价！');
        }
        $endDate = request('end_date');
        self::validateGoodsEndDate($endDate);

        // 检测名称是否包含过滤关键字
        $name = request('name');
        FilterKeywordService::filterKeywordByCategory($name, FilterKeyword::CATEGORY_GOODS_NAME);

        $goods->oper_id = $operId;
        $goods->merchant_id = $merchantId;
        $goods->name = $name;
        $goods->market_price = $marketPrice;
        $goods->price = $price;
        $goods->start_date = request('start_date');
        $goods->end_date = $endDate;
        $goods->pic = request('pic', '');
        $picList = request('pic_list', '');
        if(is_array($picList)){
            $picList = implode(',', $picList);
        }
        $goods->pic_list = $picList;

        $goods->thumb_url = request('thumb_url', '');
        $goods->desc = request('desc', '');
        $goods->buy_info = request('buy_info', '');
        $goods->status = request('status', 1);
        $goods->sort = self::getMaxSort($merchantId) + 1;
        $goods->stock = request('stock', 0);
        $goods->save();

        // 更新商户最低价格
        MerchantService::updateMerchantLowestAmount($merchantId);

        return $goods;
    }

    /**
     * 编辑商品信息
     * @param $id
     * @param $merchantId
     * @return Goods
     */
    public static function editFromRequest($id, $merchantId)
    {
        $goods = self::getByIdAndMerchantId($id, $merchantId);
        if(empty($goods)){
            throw new DataNotFoundException('商品信息不存在或已被删除');
        }

        $marketPrice = request('market_price', 0);
        $price = request('price', 0);
        if($marketPrice <= $price){
            throw new BaseResponseException('市场价不能小于销售价！');
        }
        $endDate = request('end_date');
        self::validateGoodsEndDate($endDate);

        // 检测名称是否包含过滤关键字
        $name = request('name');
        FilterKeywordService::filterKeywordByCategory($name, FilterKeyword::CATEGORY_GOODS_NAME);

        $goods->name = $name;
        $goods->market_price = $marketPrice;
        $goods->price = $price;
        $goods->start_date = request('start_date');
        $goods->end_date = $endDate;
        $goods->pic = request('pic', '');
        $picList = request('pic_list', '');
        if(is_array($picList)){
            $picList = implode(',', $picList);
        }
        $goods->pic_list = $picList;
        $goods->thumb_url = request('thumb_url', '');
        $goods->desc = request('desc', '');
        $goods->buy_info = request('buy_info', '');
        $goods->status = request('status', 1);

        $goods->stock = request('stock', 0);

        $goods->save();

        // 更新商户最低价格
        MerchantService::updateMerchantLowestAmount(request()->get('current_user')->merchant_id);

        return $goods;
    }

    /**
     * 修改商品状态
     * @param $id
     * @param $merchantId
     * @param $status
     * @return Goods
     */
    public static function changeStatus($id, $merchantId, $status)
    {
        $goods = self::getByIdAndMerchantId($id, $merchantId);
        if(empty($goods)){
            throw new DataNotFoundException('商品信息不存在或已被删除');
        }

        if ($status == Goods::STATUS_ON) {
            self::validateGoodsEndDate($goods->end_date);
        }

        $goods->status = $status;
        $goods->save();

        // 更新商户最低价格
        MerchantService::updateMerchantLowestAmount($merchantId);

        return $goods;
    }

    /**
     * @param $id
     * @param $merchantId
     * @return Goods
     * @throws \Exception
     */
    public static function del($id, $merchantId)
    {

        $goods = self::getByIdAndMerchantId($id, $merchantId);
        if(empty($goods)){
            throw new DataNotFoundException('商品信息不存在或已被删除');
        }
        $goods->delete();

        MerchantService::updateMerchantLowestAmount($merchantId);
        return $goods;
    }

    public static function changeSort($id, $merchantId, $type = 'up')
    {
        if ($type == 'down'){
            $option = '<';
            $order = 'desc';
        }else{
            $option = '>';
            $order = 'asc';
        }

        $goods = self::getByIdAndMerchantId($id, $merchantId);
        if (empty($goods)){
            throw new BaseResponseException('该团购商品不存在');
        }
        $goodsExchange = $goods::where('merchant_id', $merchantId)
            ->where('sort', $option, $goods['sort'])
            ->orderBy('sort', $order)
            ->first();
        if (empty($goodsExchange)){
            throw new BaseResponseException('交换位置的团购商品不存在');
        }

        $item = $goods['sort'];
        $goods['sort'] = $goodsExchange['sort'];
        $goodsExchange['sort'] = $item;

        try{
            DB::beginTransaction();
            $goods->save();
            $goodsExchange->save();
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw new BaseResponseException('交换位置失败');
        }
    }

    /**
     * 初始化新加排序字段sort的数值
     * @author andy
     */
    public static function initSortData()
    {
        Goods::chunk(500, function ($goods) {
            foreach ($goods as $good) {
                $good->sort = $good->id;
                $good->save();
            }
        });
    }

    /**
     * 获取当前最大排序值
     * @param $merchantId
     * @return int|number
     */
    private static function getMaxSort($merchantId)
    {
        $sort = Goods::where('merchant_id', $merchantId)->max('sort');
        return $sort ?? 0;
    }

    /**
     * 验证商品结束日期是否合法日期
     * @param $endDate
     * @return bool
     */
    private static function validateGoodsEndDate($endDate)
    {
        if ($endDate < date('Y-m-d', time())) {
            throw new ParamInvalidException('商品有效期结束时间不能小于当前时间');
        } else {
            return true;
        }
    }

    public static function userGoodsList($merchant_id)
    {
        $merchant = Merchant::findOrFail($merchant_id);
        $list = Goods::where('merchant_id', $merchant_id)
            ->where('status', Goods::STATUS_ON)
            ->where('stock','>',0)
            ->orderBy('sort', 'desc')
            ->get();
        $list->each(function ($item) use ($merchant) {
            $item->pic_list_new = $item->pic_list;
            $item->pic_list_arr_new = $item->pic_list ? explode(',', $item->pic_list) : [];
            $item->pic_list = $item->pic_list ? explode(',', $item->pic_list) : [];
            $item->business_time = json_decode($merchant->business_time, 1);
        });

        if ($list) {
            $goods_ids = [];
            $list->each(function ($item) use (&$goods_ids){
                $goods_ids[] = $item->id;
                $item->marketing_started = (object) [];
                $item->marketing_soon = (object) [];
            });

            if ($goods_ids) {
                $goods_ids = implode(',',$goods_ids);
                $groupBookList = GoodsService::getGroupBookByIds($goods_ids);
                $marketing_info_ori = MarketingApi::addMarketingInfo($merchant_id,1,$goods_ids);
                $marketing_info = $marketing_info_ori['list'];
                if (empty($marketing_info)) {
                    return $list;
                }
                $sort = 1;

                $haveGroupBookGoods = false;
                $list = $list->each(function ($item) use (
                    $marketing_info,
                    $marketing_info_ori,
                    &$sort,
                    &$haveGroupBookGoods,
                    $groupBookList
                ) {
                    $item->is_marketing = 0;
                    if (!empty($marketing_info[$item->id]['marketing_started'])) {
                        $item->is_marketing = 1;
                        $item->marketing_started->user_limit = $marketing_info[$item->id]['marketing_started']['limited'];
                        $item->marketing_started->sales_count = $marketing_info[$item->id]['marketing_started']['sales_count'];
                        $item->marketing_started->marketing_stock = $marketing_info[$item->id]['marketing_started']['stock'];
                        $item->marketing_started->discount_price = $marketing_info[$item->id]['marketing_started']['discount_price'];
                        $item->marketing_started->marketing_id = $marketing_info[$item->id]['marketing_started']['marketing_id'];
                        $item->marketing_started->phase_id = $marketing_info[$item->id]['marketing_started']['phase_id'];
                        $phase = $marketing_info_ori['phase_started'];
                        $item->marketing_started->end_time_left = strtotime($phase['end_time']) - time();
                    }

                    if (!empty($marketing_info[$item->id]['marketing_soon'])) {
                        $item->marketing_soon->user_limit = $marketing_info[$item->id]['marketing_soon']['limited'];
                        $item->marketing_soon->sales_count = $marketing_info[$item->id]['marketing_soon']['sales_count'];
                        $item->marketing_soon->marketing_stock = $marketing_info[$item->id]['marketing_soon']['stock'];
                        $item->marketing_soon->discount_price = $marketing_info[$item->id]['marketing_soon']['discount_price'];
                        $item->marketing_soon->marketing_id = $marketing_info[$item->id]['marketing_soon']['marketing_id'];
                        $item->marketing_soon->phase_id = $marketing_info[$item->id]['marketing_soon']['phase_id'];
                        $phase = $marketing_info_ori['phase_soon'];
                        $item->marketing_soon->start_time_left = strtotime($phase['start_time']) - time();
                    }
                    // 团购超值拼团数据
                    GoodsService::insertGroupBookDetail($item, 1, $groupBookList[$item->id] ?? []);
                    if ($item->is_group_booking==GoodsService::GROUP_BOOKING_ON) {
                        $haveGroupBookGoods = true;
                    }
                    $sort++;
                });
                if ($haveGroupBookGoods) {
                    $list = $list->sortByDesc('is_group_booking')
                        ->values()
                        ->all();
                }
            }

        }

        return $list;
    }

    public static function insertGroupBookingData(&$item, $groupBooking=[])
    {
        if(empty($groupBooking)){
            return ;
        }
        $item->group_booking = (object) [];
        $now = date('Y-m-d H:i:s');
        $item->is_group_booking = ($groupBooking['pintuan_start_time']>$now || $groupBooking['pintuan_end_time']<$now) ? self::GROUP_BOOKING_OFF : self::GROUP_BOOKING_ON;
        $item->group_booking->people_number = $groupBooking['pintuan_people_number'];
        $item->group_booking->total_count = $groupBooking['pintuan_total_count'];
        $item->group_booking->start_time = $groupBooking['pintuan_start_time'];
        $item->group_booking->end_time = $groupBooking['pintuan_end_time'];
        $item->group_booking->limited = $groupBooking['limited'];
        $item->group_booking->sales_count = $groupBooking['sales_count'];
        $item->group_booking->stock = $groupBooking['stock'];
        $item->group_booking->marketing_id = $groupBooking['marketing_id'];
        $item->group_booking->phase_id = $groupBooking['phase_id'];
        $item->group_booking->price = $groupBooking['price'];
        $item->group_booking->discount_price = $groupBooking['discount_price'];
        $item->group_booking->sort_number = $groupBooking['sort_number'];
        $item->group_booking->team_count = $groupBooking['team_count'];
    }

    /**
     * 获取团购商品
     * @param int $merchantId
     * @param $insertDistance
     * @param $page
     * @param $pageSize
     * @return array
     */
    public static function getGroupBookGoods($merchantId, $insertDistance, $page, $pageSize)
    {
        $groupBookGoods = MarketingApi::getGroupBookingFromMarketing($goodsType=1, $merchantType=1, $merchantId);
        if(!$groupBookGoods){
            return [];
        }
        $goodsIds = array_column($groupBookGoods,'goods_id');
        $query = Goods::where('status',Goods::STATUS_ON)
            ->where('stock','>',0)
            ->whereIn('id',$goodsIds);
        if($insertDistance!=false) {
            // 按距离查询
            $allList = $query->get();
            $count = $query->count();
            $list = $allList->each(function($item) use ($groupBookGoods, $goodsIds, $insertDistance) {
                $key = array_search($item->id, $goodsIds);
                GoodsService::insertGroupBookingData($item, $groupBookGoods[$key]);
                $insertDistance($item);
            })
                ->sortBy('distance_ori')
                ->forPage($page, $pageSize)
                ->values()
                ->all();
        }else{
            $data = $query->paginate();
            $list = $data->items();
            $count = $data->total();
        }
        return ['list'=>$list, 'total'=>$count];

    }

    public static function userGoodsDetail($id){
        $detail = Goods::findOrFail($id);
        $detail->pic_list = $detail->pic_list ? explode(',', $detail->pic_list) : [];
        $merchant = Merchant::findOrFail($detail->merchant_id);
        $detail->business_time = json_decode($merchant->business_time, 1);

        return $detail;
    }

    public static function getListByIdsToMarketing($ids)
    {
        return Goods::whereIn('id',$ids)->get();
    }

    public static function getListByNameNMerchantIdToMarketing($name,$merchantId)
    {
        return Goods::where('merchant_id',$merchantId)
                        ->where('name','like','%'.$name.'%')
                        ->where('status',Goods::STATUS_ON)
                        ->get();
    }

    /**
     * @param $id
     * @param $merchantId
     * @return Goods
     */
    public static function getByIdNMerchantIdToMarketing($id,$merchantId)
    {
        $good = Goods::where('id',$id)
                    ->where("merchant_id",$merchantId)
                    ->first();
        if(!$good){
            throw new BaseResponseException('该商品不存在');
        }
        return $good;
    }

    /**
     * 商户团购商品添加库存
     * @param $id
     * @param $addStockNum
     * @return Goods
     */
    public static function addStock($id, $addStockNum)
    {
        $goods = self::getById($id);
        if (empty($goods)) {
            throw new BaseResponseException('该团购商品不存在');
        }
        $goods->stock += $addStockNum;
        $goods->save();

        return $goods;
    }

    /**
     * 注入距离信息闭包
     * @param $currentDeviceNo
     * @param $lng
     * @param $lat
     * @return \Closure
     */
    public static function insertDistance($currentDeviceNo, $lng, $lat)
    {
        return function($item) use ($lng,$lat,$currentDeviceNo){
            $item->distance_ori = Lbs::getDistanceOfMerchant($item->merchant_id, $currentDeviceNo, floatval($lng), floatval($lat));
            $item->distance = Utils::getFormativeDistance($item->distance_ori);
            return $item;
        };
    }

    public static function insertGroupBookDetail(&$goods, $goodsType, $groupGoods=null)
    {
        $goods->is_group_booking =  GoodsService::GROUP_BOOKING_OFF;
        $groupGoods = $groupGoods ?? MarketingApi::getGroupBookingGoodsFromMarketingById($goodsType, $goods->id);
        if($groupGoods){
            GoodsService::insertGroupBookingData($goods, $groupGoods);
        }
        return $goods;
    }
}
