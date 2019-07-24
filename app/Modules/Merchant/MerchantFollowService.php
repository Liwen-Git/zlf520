<?php

namespace App\Modules\Merchant;

use App\BaseService;
use App\DataCacheService;
use App\Exceptions\BaseResponseException;
use App\Modules\Cs\CsActivityService;
use App\Modules\Cs\CsMerchant;
use App\Modules\Cs\CsMerchantService;
use App\Modules\CsStatistics\CsStatisticsMerchantOrder;
use App\Modules\Goods\GoodsService;
use App\Modules\Oper\Oper;
use App\Support\Lbs;
use App\Support\Utils;
use Illuminate\Database\Eloquent\Builder;


/**
 * 商户关注相关service
 * Class MerchantFollowService
 * @package App\Modules\Merchant
 */
class MerchantFollowService extends BaseService
{
    /**
     * 修改商户关注状态
     * @param $params
     * @return int
     */
    public static function modifyFollows($params)
    {
        $status = array_get($params, "status");
        $userId = array_get($params,'user_id');
        $merchantId = array_get($params,'merchant_id');
        $merchantType = array_get($params, 'merchant_type');

        if (!in_array($merchantType, [MerchantFollow::MERCHANT_TYPE_NORMAL, MerchantFollow::MERCHANT_TYPE_CS])) {
            throw new BaseResponseException('商户类型错误');
        }

        $merchantFollowQuery = MerchantFollow::where('user_id',$userId)
            ->where('merchant_id',$merchantId)
            ->where('merchant_type', $merchantType)
            ->first();

        if($status == MerchantFollow::USER_NOT_FOLLOW){
            //未关注，增加记录
            if($merchantFollowQuery){
                if($merchantFollowQuery->status == MerchantFollow::USER_NOT_FOLLOW){
                    $merchantFollowQuery->status = MerchantFollow::USER_YES_FOLLOW;
                    $merchantFollowQuery->save();
                }else{
                    throw new BaseResponseException('已关注');
                }
            }else{
                $merchantFollow = new MerchantFollow();
                $merchantFollow->merchant_id = $merchantId;
                $merchantFollow->merchant_type = $merchantType;
                $merchantFollow->user_id = $userId;
                $merchantFollow->status = MerchantFollow::USER_YES_FOLLOW;
                $merchantFollow->save();
            }

            if ($merchantType == MerchantFollow::MERCHANT_TYPE_NORMAL) {
                Merchant::where('id', $merchantId)->increment('user_follows');
            } else {
                CsMerchant::where('id', $merchantId)->increment('user_follows');
            }
            $follow_status = MerchantFollow::USER_YES_FOLLOW; //返回已关注状态
        }else{
            if($merchantFollowQuery->status == MerchantFollow::USER_NOT_FOLLOW){
                throw new BaseResponseException('已取消');
            }else{
                $merchantFollowQuery->status = MerchantFollow::USER_NOT_FOLLOW;
                $merchantFollowQuery->save();
            }

            if ($merchantType == MerchantFollow::MERCHANT_TYPE_NORMAL) {
                Merchant::where('id', $merchantId)->decrement('user_follows');
            } else {
                CsMerchant::where('id', $merchantId)->decrement('user_follows');
            }
            $follow_status = MerchantFollow::USER_NOT_FOLLOW; //返回未关注状态
        }
        return $follow_status;
    }

    /**
     * 获取用户关注列表
     * @param $userId
     * @param $lng
     * @param $lat
     * @param bool $onlyPayToPlatform
     * @return array
     */
    public static function getFollowMerchantList($userId,$lng,$lat,$onlyPayToPlatform =false)
    {
        $query = Merchant::where('merchants.status', 1)
            ->where('merchants.oper_id', '>', 0)
            ->join('merchant_follows', function($query) use ($userId) {
                $query->on('merchant_follows.merchant_id', '=', 'merchants.id')
                    ->where('merchant_follows.user_id', $userId)
                ->where('merchant_follows.status', MerchantFollow::USER_YES_FOLLOW)
                ->where('merchant_follows.merchant_type', MerchantFollow::MERCHANT_TYPE_NORMAL);
            })
            ->when($onlyPayToPlatform, function (Builder $query) {
                $query->whereHas('oper', function(Builder $query){
                    $query->whereIn('pay_to_platform', [ Oper::PAY_TO_PLATFORM_WITHOUT_SPLITTING, Oper::PAY_TO_PLATFORM_WITH_SPLITTING ]);
                });
            })
            ->whereIn('merchants.audit_status', [Merchant::AUDIT_STATUS_SUCCESS, Merchant::AUDIT_STATUS_RESUBMIT])
            ->select('merchants.*')
            ->selectRaw('merchant_follows.updated_at as merchant_follows_updated_at')
            ->orderBy('merchant_follows_updated_at', 'desc');

        if($lng && $lat){
            // 如果是按距离搜索, 需要在程序中按距离排序
            $allList = $query->get();
            $total = $query->count();
            $list = $allList->map(function ($item) use ($lng, $lat, $userId) {
                $item->distance = Lbs::getDistanceOfMerchant($item->id, $userId, floatval($lng), floatval($lat));

                if ($item->is_pilot == 1) {
                    $item->distance += 100000000;
                }
                return $item;
            })
                ->forPage(request('page', 1), 15)
                ->values()
                ->map(function($item) {
                    if ($item->is_pilot == 1) {
                        $item->distance -= 100000000;
                    }
                    $item->distance = Utils::getFormativeDistance($item->distance);
                    $merchant = DataCacheService::getMerchantDetail($item->id);
                    $merchant->distance = $item->distance;
                    // 格式化距离
                    return $merchant;
                });
        }else {
            // 没有按距离搜索时, 直接在数据库中排序并分页
            $query->orderBy('is_pilot', 'asc');
            $data = $query->paginate();
            $list = $data->items();
            $total = $data->total();
        }

        // 补充商家其他信息
        $list = collect($list);
        $list->each(function ($item){
            $item->desc_pic_list = $item->desc_pic_list ? explode(',', $item->desc_pic_list) : [];
            if($item->business_time) $item->business_time = json_decode($item->business_time, 1);
            $category = MerchantCategory::find($item->merchant_category_id);
            $item->merchantCategoryName = $category->name;
            // 最低消费
            $item->lowestAmount = MerchantService::getLowestPriceForMerchant($item->id);
            // 兼容v1.0.0版客服电话字段
            $item->contacter_phone = $item->service_phone;
            // 商户评级字段，暂时全部默认为5星
            $item->grade = 5;
            // 首页商户列表，显示价格最低的n个团购商品
            $item->lowestGoods = GoodsService::getLowestPriceGoodsForMerchant($item->id, 2);
        });

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 获取用户的超市关注列表
     * @param $userId
     * @param array $params
     * @return array
     */
    public static function getFollowCsMerchantList($userId, array $params = [])
    {
        $lng = array_get($params,'lng');
        $lat = array_get($params, 'lat');
        $pageSize = array_get($params, 'pageSize',15);

        $user_key = array_get($params, 'user_key', 0); //终端的唯一表示，用于计算距离

        //只能查询切换到平台的商户
        $query = CsMerchant::where('status', CsMerchant::STATUS_ON)
            ->where('oper_id', '>', 0)
            ->whereHas('merchantFollow', function (Builder $query) use ($userId) {
                $query->where('user_id',$userId)
                    ->where('status',MerchantFollow::USER_YES_FOLLOW)
                    ->where('merchant_type', MerchantFollow::MERCHANT_TYPE_CS);
            })
            ->whereIn('audit_status', [CsMerchant::AUDIT_STATUS_SUCCESS]);

        if($lng && $lat){
            // 如果是按距离搜索, 需要在程序中按距离排序
            $allList = $query->select('id')->get();
            $total = $query->count();
            $list = $allList->map(function ($item) use ($lng, $lat, $user_key) {
                $item->distance = Lbs::getDistanceOfCsMerchant($item->id, $user_key, floatval($lng), floatval($lat));
                return $item;
            })
                ->sortBy('distance')
                ->forPage(request('page', 1), $pageSize)
                ->values()
                ->map(function($item) {
                    if ($item->is_pilot == 1) {
                        $item->distance -= 100000000;
                    }
                    $item->distance = Utils::getFormativeDistance($item->distance);
                    $merchant = DataCacheService::getCsMerchantDetail($item->id);
                    $merchant->distance = $item->distance;
                    // 格式化距离
                    return $merchant;
                });
        }else {
            // 没有按距离搜索时, 直接在数据库中排序并分页
            $data = $query->paginate($pageSize);
            $list = $data->items();
            $total = $data->total();
        }

        // 补充商家其他信息
        $list = collect($list);
        $list->each(function ($item){
            if(!CsActivityService::isOpenHotSell()){
                $item->hot_status = 0;
            }

            $item->desc_pic_list = $item->desc_pic_list ? explode(',', $item->desc_pic_list) : [];
            if($item->business_time) $item->business_time = json_decode($item->business_time, 1);
            // 兼容v1.0.0版客服电话字段
            $item->contacter_phone = $item->service_phone;
            // 商户评级字段，暂时全部默认为5星
            $item->grade = 5;

            //商户销量:
            $merchantOrderData = CsStatisticsMerchantOrder::where('cs_merchant_id',$item->id)->select('order_number_30d','order_number_today')->first();
            if($merchantOrderData){
                $item->month_order_number = $merchantOrderData->order_number_today + $merchantOrderData->order_number_30d;//月销售量加入当前销售量
            }else{
                $item->month_order_number = 0;
            }

            //配送信息:
            CsMerchantService::getMerchantSetting($item);
        });

        return ['list' => $list, 'total' => $total];
    }
}