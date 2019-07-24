<?php
/**
 * Created by PhpStorm.
 * User: 57458
 * Date: 2018/7/24
 * Time: 17:25
 */

namespace App\Modules\Dishes;


use App\BaseService;
use App\Exceptions\BaseResponseException;
use App\Exceptions\DataNotFoundException;
use App\Support\MarketingApi;
use App\Modules\FilterKeyword\FilterKeyword;
use App\Modules\FilterKeyword\FilterKeywordService;
use App\Modules\Merchant\MerchantService;
use App\Modules\Order\Order;
use App\Modules\Order\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DishesGoodsService extends BaseService
{

    public static function getById($id)
    {
        return DishesGoods::find($id);
    }

    public static function getByIdAndMerchantId($id, $merchantId)
    {
        return DishesGoods::where('id', $id)->where('merchant_id', $merchantId)->first();
    }

    /**
     * 获取最大的排序值, 传categoryId时获取当前分类下的最大排序值
     * @param $merchantId
     * @param int $categoryId
     * @return int|number
     */
    public static function getMaxSort($merchantId, $categoryId = null)
    {
        $sort = DishesGoods::where('merchant_id', $merchantId)
            ->when(!empty($categoryId), function (Builder $query) use ($categoryId){
                $query->where('dishes_category_id', $categoryId);
            })->max('sort');
        return $sort ?? 0;
    }

    /**
     * 通过merchant_id分类获取单品商品列表
     * @param $merchantId
     * @return DishesGoods[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getHotDishesGoods($merchantId)
    {
        $list =DishesGoods::where('merchant_id', $merchantId)
            ->select('*')
            ->where('status', 1)
            ->where('is_hot',1)
            ->selectRaw('detail_image as detail_imgs')
            ->get();

        if ($list) {
            $goods_ids = [];
            $list->each(function ($item) use (&$goods_ids){
                $goods_ids[] = $item->id;
                $item->marketing_started = (object) [];
                $item->marketing_soon = (object) [];
            });

            if ($goods_ids) {
                $goods_ids = implode(',',$goods_ids);
                $marketing_info_ori = MarketingApi::addMarketingInfo($merchantId,2,$goods_ids);
                $marketing_info = $marketing_info_ori['list'];
                $list->each(function ($item) use ($marketing_info,$marketing_info_ori) {

                    if (!empty($marketing_info[$item->id]['marketing_started'])) {
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
                });
            }

        }
        return $list;
    }

    /**
     * 获取单品指定分类的商品
     * @param $merchantId
     * @param $categoryId
     * @return DishesGoods[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getDishesGoods($merchantId,$categoryId)
    {
        $list = DishesGoods::where('merchant_id', $merchantId)
            ->select('*')
            ->where('status', 1)
            ->where('dishes_category_id',$categoryId)
            ->orderBy('sort', 'desc')
            ->selectRaw('detail_image as detail_imgs')
            ->get();

        if ($list) {
            $goods_ids = [];
            $list->each(function ($item) use (&$goods_ids){
                $goods_ids[] = $item->id;
                $item->marketing_started = (object) [];
                $item->marketing_soon = (object) [];
            });

            if ($goods_ids) {
                $goods_ids = implode(',',$goods_ids);
                $marketing_info_ori = MarketingApi::addMarketingInfo($merchantId,2,$goods_ids);
                $marketing_info = $marketing_info_ori['list'];
                $list->each(function ($item) use ($marketing_info,$marketing_info_ori) {

                    if (!empty($marketing_info[$item->id]['marketing_started'])) {
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
                });
            }

        }
        return $list;
    }

    /**
     * 获取单品商品列表
     * @param $params array 查询参数 {merchantId, status, name, categoryId}
     * @param $pageSize
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getList($params, $pageSize)
    {
        $merchantId = array_get($params, 'merchantId');
        $status = array_get($params, 'status');
        $name = array_get($params, 'name');
        $categoryId = array_get($params, 'categoryId');

        $data = DishesGoods::when($merchantId, function (Builder $query) use ($merchantId) {
            $query->where('merchant_id', $merchantId);
        })
            ->when($status, function (Builder $query) use ($status) {
                $query->where('status', $status);
            })
            ->when($name, function (Builder $query) use ($name) {
                $query->where('name', 'like', "%$name%");
            })
            ->when($categoryId, function (Builder $query) use ($categoryId) {
                $query->where('dishes_category_id', $categoryId);
            })
            ->orderBy('sort', 'desc')
            ->with('dishesCategory:id,name')
            ->paginate($pageSize);

        return $data;
    }

    /**
     * 添加单品商品
     * @param $operId
     * @param $merchantId
     * @return DishesGoods
     */
    public static function addFromRequest($operId, $merchantId)
    {
        $name = request('name');
        $dishesGoodsList = DishesGoods::where('merchant_id', $merchantId)
            ->where('name', $name)
            ->first();
        if (!empty($dishesGoodsList)) {
            throw new BaseResponseException('商品名称重复！');
        }
        $marketPrice = request('market_price', 0);
        $salePrice = request('sale_price', 0);
        if ($marketPrice < $salePrice) {
            throw new BaseResponseException('市场价不能小于销售价！');
        }
        // 检测商品名中是否存在过滤关键字
        FilterKeywordService::filterKeywordByCategory($name, FilterKeyword::CATEGORY_DISHES_GOODS_NAME);

        $categoryId = request('dishes_category_id', 0);
        $dishesGoods = new DishesGoods();
        $dishesGoods->oper_id = $operId;
        $dishesGoods->merchant_id = $merchantId;
        $dishesGoods->name = $name;
        $dishesGoods->market_price = $marketPrice;
        $dishesGoods->sale_price = $salePrice;
        $dishesGoods->dishes_category_id = $categoryId;
        $dishesGoods->intro = request('intro', '');
        $dishesGoods->detail_image = request('detail_image', '');
        $dishesGoods->status = request('status', 1);
        $dishesGoods->is_hot = request('is_hot', 0);
        $dishesGoods->sort = self::getMaxSort($merchantId, $categoryId) + 1;
        $dishesGoods->stock = request('stock', 0);

        $dishesGoods->save();

        // 更新商家最低消费价
        MerchantService::updateMerchantLowestAmount($merchantId);

        return $dishesGoods;
    }

    /**
     * 编辑商品信息
     * @param $id
     * @param $merchantId
     * @return DishesGoods
     */
    public static function editFromRequest($id, $merchantId)
    {
        $name = request('name');
        $dishesGoods = DishesGoods::where('merchant_id', $merchantId)
            ->where('name', $name)
            ->where('id', '<>', $id)
            ->first();
        if (!empty($dishesGoods)){
            throw new BaseResponseException('商品名称重复！');
        }

        $dishesGoods = self::getByIdAndMerchantId($id, $merchantId);
        if(empty($dishesGoods)){
            throw new DataNotFoundException('商品信息不存在或已被删除');
        }
        $marketPrice = request('market_price', 0);
        $salePrice = request('sale_price', 0);
        if($marketPrice < $salePrice){
            throw new BaseResponseException('市场价不能小于销售价！');
        }
        // 检测商品名中是否存在过滤关键字
        FilterKeywordService::filterKeywordByCategory($name, FilterKeyword::CATEGORY_DISHES_GOODS_NAME);

        $dishesGoods->name = $name;
        $dishesGoods->market_price = $marketPrice;
        $dishesGoods->sale_price = $salePrice;
        $dishesGoods->dishes_category_id = request('dishes_category_id', 0);
        $dishesGoods->intro = request('intro', '');
        $dishesGoods->detail_image = request('detail_image', '');
        $dishesGoods->status = request('status', 1);
        $dishesGoods->is_hot = request('is_hot', 0);
        $dishesGoods->stock = request('stock', 0);

        $dishesGoods->save();

        // 更新商家最低消费价
        MerchantService::updateMerchantLowestAmount(request()->get('current_user')->merchant_id);

        return $dishesGoods;
    }

    /**
     * 修改商品状态
     * @param $id
     * @param $merchantId
     * @param $status
     * @return DishesGoods
     */
    public static function changeStatus($id, $merchantId, $status)
    {
        $dishesGoods = self::getByIdAndMerchantId($id, $merchantId);
        if(empty($dishesGoods)){
            throw new DataNotFoundException('商品信息不存在或已被删除');
        }
        $dishesGoods->status = $status;
        $dishesGoods->save();

        // 更新商户最低价格
        MerchantService::updateMerchantLowestAmount($merchantId);

        return $dishesGoods;
    }

    /**
     * 删除单品商品
     * @param $id
     * @param $merchantId
     * @return DishesGoods
     * @throws \Exception
     */
    public static function del($id, $merchantId)
    {
        $dishesGoods = self::getByIdAndMerchantId($id, $merchantId);
        if(empty($dishesGoods)){
            throw new DataNotFoundException('商品信息不存在或已被删除');
        }
        $dishesGoods->delete();
        MerchantService::updateMerchantLowestAmount($merchantId);
        return $dishesGoods;
    }

    public static function changeSort($id, $merchantId, $categoryId = null, $type = 'up')
    {
        if ($type == 'down'){
            $option = '<';
            $order = 'desc';
        }else{
            $option = '>';
            $order = 'asc';
        }

        $dishesGoods = self::getByIdAndMerchantId($id, $merchantId);
        if (empty($dishesGoods)){
            throw new BaseResponseException('商品信息不存在或已被删除');
        }
        $dishesGoodsExchange = DishesGoods::where('merchant_id', $merchantId)
            ->where('sort', $option, $dishesGoods['sort'])
            ->when($categoryId, function (Builder $query) use ($categoryId) {
                $query->where('dishes_category_id', $categoryId);
            })
            ->orderBy('sort', $order)
            ->first();
        if (empty($dishesGoodsExchange)){
            throw new BaseResponseException('交换位置的单品不存在');
        }

        $item = $dishesGoods['sort'];
        $dishesGoods['sort'] = $dishesGoodsExchange['sort'];
        $dishesGoodsExchange['sort'] = $item;

        try{
            DB::beginTransaction();
            $dishesGoods->save();
            $dishesGoodsExchange->save();
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw new BaseResponseException('交换位置失败');
        }
    }

    public static function getListByIdsToMarketing($ids)
    {
        $query = DishesGoods::whereIn('id',$ids);
        return self::getChangeColumnName($query)->get();
    }

    public static function getListByNameNMerchantIdToMarketing($name,$merchantId)
    {
        $query = DishesGoods::where('merchant_id',$merchantId)
            ->where('name','like','%'.$name.'%')
            ->where('status',DishesGoods::STATUS_ON);
        return self::getChangeColumnName($query)->get();
    }

    public static function getChangeColumnName($query)
    {
        return $query->select('*')
            ->selectRaw('detail_image as thumb_url')
            ->selectRaw('detail_image as pic_list')
            ->selectRaw('sale_price as price')
            ->selectRaw('"" as buy_info')
            ->selectRaw('intro as "desc"')
            ->selectRaw('"" as start_date')
            ->selectRaw('"" as end_date');
    }

    /**
     * @param $id
     * @param $merchantId
     * @return mixed
     */
    public static function getByIdNMerchantIdToMarketing($id,$merchantId)
    {
        $query = DishesGoods::where('id',$id)
                    ->where('merchant_id',$merchantId);
        $good = self::getChangeColumnName($query)->first();
        if(!$good){
            throw new BaseResponseException('该商品不存在');
        }
        return $good;
    }

    public static function getPhaseGoodsList($dishesId,$marketingId,$phaseId){
        $returnList = [];
        $goodsList = DishesItem::where(['dishes_id'=>$dishesId])
                    ->where('phase_id',$phaseId)
                    ->where('marketing_id',$marketingId)
                    ->get();
        if(!empty($goodsList)){
            foreach ($goodsList as $good){
                $returnList[] = [
                    'goods_id'  =>  $good->dishes_goods_id,
                    'price'     =>  $good->ori_price,
                    'discount_price'=>$good->dishes_goods_sale_price,
                    'buy_number'=>  $good->number,
                    'total_price'=> $good->price*$good->number,
                    'goods_type'=>  OrderService::MARKETING_GOODS_TYPE_DISHED,
                ];
            }
        }
        return $returnList;
    }

    public static function userGoodsList($merchantId)
    {
        $query = DishesGoods::where('merchant_id',$merchantId)
                            ->where('status',DishesGoods::STATUS_ON);
        return self::getChangeColumnName($query)->get();
    }

    /**
     * 订单取消增加库存
     * @param $dishes_id
     * @return bool
     */
    public static function orderCancel($dishes_id)
    {

        $list = DishesItem::where('dishes_id',$dishes_id)->get();
        if (empty($list)) {
            return false;
        }

        foreach ($list as $item) {
            $good = DishesGoods::find($item->dishes_goods_id);
            if (!$good) {
                continue;
            }
            $good->stock += $item->number;
            $good->save();
        }

        return true;
    }

    /**
     * 商户单品添加库存
     * @param $id
     * @param $addStockNum
     * @return DishesGoods
     */
    public static function addStock($id, $addStockNum)
    {
        $goods = self::getById($id);
        if (empty($goods)) {
            throw new BaseResponseException('该单品不存在');
        }
        $goods->stock += $addStockNum;
        $goods->save();

        return $goods;
    }
}
