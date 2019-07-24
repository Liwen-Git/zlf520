<?php
/**
 * Created by PhpStorm.
 * User: tim.tang
 * Date: 2018/11/20/020
 * Time: 14:39
 */
namespace App\Modules\Cs;

use App\BaseService;
use App\DataCacheService;
use App\Exceptions\BaseResponseException;
use App\Exceptions\DataNotFoundException;
use App\Http\Controllers\UserApp\GroupBookController;
use App\Modules\Goods\GoodsService;
use App\ResultCode;
use App\Modules\Order\Order;
use App\Support\MarketingApi;
use App\Modules\Setting\SettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Self_;

class CsGoodService extends BaseService
{

    const CAT_TYPE_NORMAL = 1;
    const CAT_TYPE_FLASH_SALE = 2;
    const CAT_TYPE_GROUP_BOOK = 3;
    /**
     * 查询商品列表
     * @param array $params
     * @param bool $getWithQuery
     * @return mixed
     */
    public static function getList(array $params = [], bool $getWithQuery = false)
    {

        $query = CsGood::select('*')
            ->when(!empty($params['hot_status']),function (Builder $query) use ($params){
                $query->whereIn('hot_status', is_array($params['hot_status'])?$params['hot_status']:[$params['hot_status']]);
            })

            ->when(!empty($params['id']),function (Builder $query) use ($params){
                $query->where('id','=', $params['id']);
            })
            ->when(!empty($params['oper_id']),function (Builder $query) use ($params){
                $query->where('oper_id','=', $params['oper_id']);
            })
            ->when(!empty($params['cs_merchant_id']),function (Builder $query) use ($params){
                $query->where('cs_merchant_id','=', $params['cs_merchant_id']);
            })
            ->when(!empty($params['hot_add_time']),function (Builder $query) use ($params){
                $query->whereNotNull('hot_add_time');
            })
            ->when(!empty($params['goods_name']),function (Builder $query) use ($params){
                $query->where('goods_name','like', "%{$params['goods_name']}%");
            })
            ->when(!empty($params['cs_platform_cat_id_level1']),function (Builder $query) use ($params){
                $query->where('cs_platform_cat_id_level1','=', $params['cs_platform_cat_id_level1']);
            })
            ->when(!empty($params['cs_platform_cat_id_level2']),function (Builder $query) use ($params){
                $query->where('cs_platform_cat_id_level2','=', $params['cs_platform_cat_id_level2']);
            })
            ->when(!empty($params['status']),function (Builder $query) use ($params){
                $query->whereIn('status', $params['status']);
            })
            ->when(!empty($params['audit_status']),function (Builder $query) use ($params){
                $query->whereIn('audit_status', $params['audit_status']);
            })
            ->when(!empty($params['cs_merchant_ids']),function (Builder $query) use ($params){
                $query->whereIn('cs_merchant_id', $params['cs_merchant_ids']);
            })
            ->when(!empty($params['oper_ids']),function (Builder $query) use ($params){
                $query->whereIn('oper_id', $params['oper_ids']);
            })
            ->when(!empty($params['sort']) && !empty($params['order']),function (Builder $query) use ($params) {
                $query->orderBy($params['sort'],$params['order']);
            })
            ->when(!empty($params['sort']),function (Builder $query) use ($params){
                if ($params['sort'] == 1) {
                    $query->orderBy('sort','desc');
                } elseif ($params['sort'] == 2) {
                    $query->orderBy('created_at','desc');
                } elseif ($params['sort'] == 3) {
                    $query->orderBy('hot_total_sort','desc');
                    $query->orderBy('hot_add_time','desc');
                } else {
                    $query->orderBy('sort','desc');
                }

            })
            ->when(!empty($params['with_merchant']),function (Builder $query) use ($params){
                $query->with('cs_merchant:id,name,province,city');
            })
            ->when(!empty($params['with_oper']),function (Builder $query) use ($params){
                $query->with('oper:id,name');
            })
        ;

        if ($getWithQuery) {
            return $query;
        }
        return self::handlePageList($params,$query);
    }

    /**
     * 用户端获取商品分页列表
     * @param array $params
     * @return mixed
     */
    public static function userClientGetList(array $params = []){
        if($params['cs_platform_cat_id_level1']==-1||$params['cs_platform_cat_id_level2']==-1){
            // 如果为-1，则代表年货分类
            $params['hot_status'] = CsGood::HOT_STATUS_ON;
            $params['cs_platform_cat_id_level1']=$params['cs_platform_cat_id_level2'] = '';
        }
        $query = CsGood::select('*')
            ->selectRaw('if(stock <> 0, 1, 0) as has_stock')
            ->orderBy('has_stock','desc')
            ->when(!empty($params['cs_merchant_id']),function (Builder $query) use ($params){
                $query->where('cs_merchant_id','=', $params['cs_merchant_id']);
            })
            ->when(!empty($params['hot_status']),function (Builder $query) use ($params){
                $query->whereIn('hot_status', is_array($params['hot_status'])?$params['hot_status']:[$params['hot_status']]);
            })
            ->when(!empty($params['cs_merchant_id']),function (Builder $query) use ($params){
                $query->where('cs_merchant_id','=', $params['cs_merchant_id']);
            })
            ->when(!empty($params['hot_add_time']),function (Builder $query) use ($params){
                $query->whereNotNull('hot_add_time');
            })
            ->when(!empty($params['goods_name']),function (Builder $query) use ($params){
                $query->where('goods_name','like', "%{$params['goods_name']}%");
            })
            ->when(!empty($params['cs_platform_cat_id_level1']),function (Builder $query) use ($params){
                $query->where('cs_platform_cat_id_level1','=', $params['cs_platform_cat_id_level1']);
            })
            ->when(!empty($params['cs_platform_cat_id_level2']),function (Builder $query) use ($params){
                $query->where('cs_platform_cat_id_level2','=', $params['cs_platform_cat_id_level2']);
            })
            ->when(!empty($params['status']),function (Builder $query) use ($params){
                $query->whereIn('status', $params['status']);
            })
            ->when(!empty($params['audit_status']),function (Builder $query) use ($params){
                $query->whereIn('audit_status', $params['audit_status']);
            })
            ->when(!empty($params['cs_merchant_ids']),function (Builder $query) use ($params){
                $query->whereIn('cs_merchant_id', $params['cs_merchant_ids']);
            })
            ->when(!empty($params['sort']) && !empty($params['order']),function (Builder $query) use ($params) {
                $query->orderBy($params['sort'],$params['order']);
            })
            ->when(!empty($params['sort']),function (Builder $query) use ($params){
                if ($params['sort'] == 1) {
                    $query->orderBy('sort','desc');
                } elseif ($params['sort'] == 2) {
                    $query->orderBy('created_at','desc');
                }  else {
                    $query->orderBy('sort','desc');
                }
            });
        return self::handlePageList($params,$query);

    }

    public static function getTopGoodsLast()
    {
        $goods = CsGood::select('*')
            ->where('stock','>',0)
            ->where('status','=',CsGood::STATUS_ON)
            ->where('audit_status','=',CsGood::AUDIT_STATUS_SUCCESS)
            ->where('hot_status','=',CsGood::HOT_STATUS_ON)
//            ->whereHas('cs_merchant',function ($query) {
//                $query->where('status',CsMerchant::STATUS_ON);
//            })
            ->orderBy('hot_total_sort','desc')
            ->orderBy('hot_add_time','desc')
            ->offset(4)->limit(1)->first()
        ;


        return $goods;
    }

    public static function getTopGoods()
    {
        $data = CsGood::select('*')
            ->where('stock','>',0)
            ->where('status','=',CsGood::STATUS_ON)
            ->where('audit_status','=',CsGood::AUDIT_STATUS_SUCCESS)
            ->where('hot_status','=',CsGood::HOT_STATUS_ON)
//            ->whereHas('cs_merchant',function ($query) {
//                $query->where('status',CsMerchant::STATUS_ON);
//            })
            ->orderBy('hot_total_sort','desc')
            ->orderBy('hot_add_time','desc')
            ->limit(5)->get()
        ;

        self::hotGoodsFilter($data);
        return $data;
    }

    public static function getHotGoods($params) {
        $top_goods_last = self::getTopGoodsLast();
        if (empty($top_goods_last)) {
            return null;
        }

        $query = CsGood::select('*')
            ->where('stock','>',0)
            ->where('status','=',CsGood::STATUS_ON)
            ->where('audit_status','=',CsGood::AUDIT_STATUS_SUCCESS)
            ->where('hot_status','=',CsGood::HOT_STATUS_ON)
            ->where('hot_total_sort','<',$top_goods_last->hot_total_sort)
            ->orderBy('hot_total_sort','desc')
            ->orderBy('hot_add_time','desc')
        ;

        $pageSize = array_get($params, 'pageSize',15);
        $data = $query->paginate($pageSize);

        self::hotGoodsFilter($data);

        return $data;
    }

    public static function hotGoodsFilter(&$data)
    {
        $all_cats = DataCacheService::getPlatformCats();
        $isOpenHost = CsActivityService::isOpenHotSell();
        $data->each(function ($item) use ($all_cats,$isOpenHost) {
            $item->cs_platform_cat_id_level1_name = !empty($all_cats[$item->cs_platform_cat_id_level1])?$all_cats[$item->cs_platform_cat_id_level1]:'';
            $item->cs_platform_cat_id_level2_name = !empty($all_cats[$item->cs_platform_cat_id_level2])?$all_cats[$item->cs_platform_cat_id_level2]:'';
            // 如果未开启，则年货节状态关闭
            if(!$isOpenHost) $item->hot_status = CsGood::HOT_STATUS_OFF;


            $supermarket = DataCacheService::getCsMerchantDetail($item->cs_merchant_id);
            $MerchantInfo = CsMerchantSettingService::getDeliverSetting($supermarket->id);
            if($MerchantInfo){
                $supermarket->delivery_start_price = $MerchantInfo->delivery_start_price;
                $supermarket->delivery_charges = $MerchantInfo->delivery_charges;
                $supermarket->delivery_free_start = $MerchantInfo->delivery_free_start;
                $supermarket->delivery_free_order_amount = ($MerchantInfo->delivery_free_start!=0) ? $MerchantInfo->delivery_free_order_amount:1000000000;
            }
            $supermarket->city_limit = SettingService::getValueByKey('supermarket_city_limit');
            $supermarket->show_city_limit = SettingService::getValueByKey('supermarket_show_city_limit');
            $supermarket->province = str_replace('省','',$supermarket->province);
            $supermarket->city = str_replace('市','',$supermarket->city);
            $item->merchant_info = $supermarket;
        });
    }

    public static function getGroupBookByIds($ids)
    {
        $data = MarketingApi::getGroupBookingGoodsFromMarketingById(3, $ids);
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
     * @param $params array
     * @param $query \Illuminate\Database\Query\Builder
     * @return mixed
     */
    public static function handlePageList($params,$query)
    {
        $pageSize = array_get($params, 'pageSize',15);
        $data = $query->paginate($pageSize);
        $all_cats = DataCacheService::getPlatformCats();
        $isOpenHost = CsActivityService::isOpenHotSell();
        $goods_ids = [];
        $data->each(function ($item) use ($all_cats,$isOpenHost,&$goods_ids) {
            $item->cs_platform_cat_id_level1_name = !empty($all_cats[$item->cs_platform_cat_id_level1])?$all_cats[$item->cs_platform_cat_id_level1]:'';
            $item->cs_platform_cat_id_level2_name = !empty($all_cats[$item->cs_platform_cat_id_level2])?$all_cats[$item->cs_platform_cat_id_level2]:'';
            // 如果未开启，则年货节状态关闭
            if(!$isOpenHost) $item->hot_status = CsGood::HOT_STATUS_OFF;
            $goods_ids[] = $item->id;
            $item->marketing_started = (object) [];
            $item->marketing_soon = (object) [];
        });

        if ($goods_ids) {
            $goods_ids = implode(',',$goods_ids);
            $groupBookList = CsGoodService::getGroupBookByIds($goods_ids);
            $marketing_info_ori = MarketingApi::addMarketingInfo($params['cs_merchant_id'],3,$goods_ids);
            $marketing_info = $marketing_info_ori['list'];
            $data->each(function ($item) use ($marketing_info, $marketing_info_ori, $groupBookList) {

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
                GoodsService::insertGroupBookDetail($item, 3, $groupBookList[$item->id]??[]);
            });
        }

        return $data;
    }


    public static function changeStatus(int $id, int $cs_merchant_id)
    {
        if ($id<0 || $cs_merchant_id<0) {
            throw new BaseResponseException('参数错误1');
        }

        $goods = CsGood::findOrFail($id);
        if ($goods->cs_merchant_id != $cs_merchant_id) {
            throw new BaseResponseException('参数错误2');
        }

        if ($goods->audit_status != CsGood::AUDIT_STATUS_SUCCESS) {
            throw new BaseResponseException('商品未审核通过');
        }

        $goods->status = $goods->status == CsGood::STATUS_ON?CsGood::STATUS_OFF:CsGood::STATUS_ON;

        if ($goods->status == CsGood::STATUS_ON) {
            $cs_cat = CsMerchantCategoryService::getMerchantCat($goods->cs_merchant_id,$goods->cs_platform_cat_id_level2);
            if ($cs_cat->status == CsMerchantCategory::STATUS_OFF) {
                throw new BaseResponseException('请先上架分类:'.$cs_cat->cs_cat_name);
            }
        }


        $goods->save();

        return $goods;
    }

    public static function del(int $id, int $cs_merchant_id)
    {
        if ($id<0 || $cs_merchant_id<0) {
            throw new BaseResponseException('参数错误1');
        }

        $goods = CsGood::findOrFail($id);
        if ($goods->cs_merchant_id != $cs_merchant_id) {
            throw new BaseResponseException('参数错误2');
        }

        return $goods->delete();
    }

    /**
     * 商户查看详情
     * @param int $id
     * @param int $cs_merchant_id
     * @return CsGood
     */
    public static function detail(int $id, int $cs_merchant_id)
    {
        $goods = CsGood::findOrFail($id);
        if ($goods->cs_merchant_id != $cs_merchant_id) {
            throw new BaseResponseException('参数错误2');
        }
        return $goods;
    }

    /**
     * 运营中心查看详情
     * @param int $id
     * @param int $oper_id
     * @return CsGood
     */
    public static function operDetail(int $id, int $oper_id)
    {

        $goods = CsGood::findOrFail($id);
        if ($goods->oper_id != $oper_id) {
            throw new BaseResponseException('参数错误2');
        }
        return $goods;
    }

    /**
     * 后台查看详情
     * @param int $id
     * @param int $oper_id
     * @return CsGood
     */
    public static function adminDetail(int $id)
    {
        $goods = CsGood::findOrFail($id);
        return $goods;
    }

    /**
     * 搜索商户商品
     * @param int $cs_merchant_id
     * @param string $cs_goods_keywords
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function searchGoods(int $cs_merchant_id, string $cs_goods_keywords='')
    {

        if ($cs_merchant_id<0 || empty($cs_goods_keywords)) {
            return [];
        }

        $query = CsGood::select('id','goods_name','audit_status','status')->where('cs_merchant_id','=',$cs_merchant_id);
        if (preg_match('#^\d+$#',$cs_goods_keywords)){
            $query->where('id','=', $cs_goods_keywords);
        } else {

            $query->where('goods_name','like',"%{$cs_goods_keywords}%");
        }

        return $query->paginate(10);

    }

    /**
     * 添加年货节商品
     * @param int $cs_merchant_id
     * @param int $cs_goods_id
     * @return bool
     */
    public static function addHotGoods(int $cs_merchant_id,int $cs_goods_id)
    {
        if ($cs_merchant_id<=0 || $cs_goods_id<=0) {
            throw new BaseResponseException('参数错误');
        }

        $goods = CsGood::select('*')
            ->where('id','=', $cs_goods_id)
            ->where('cs_merchant_id','=',$cs_merchant_id)
            ->firstOrFail();

        $cs_merchant = CsMerchant::findOrFail($goods->cs_merchant_id);

        if (empty($cs_merchant->hot_add_time)) {
            throw new BaseResponseException('商户不是年货节活动商户');
        }

        if (!empty($goods->hot_add_time)) {
            throw new BaseResponseException('年货节商品已经存在');
        }

        $goods->hot_add_time = date('Y-m-d H:i:s');
        $goods->hot_status = CsGood::HOT_STATUS_OFF;

        $max_sort = self::getHotGoodsMaxSort($cs_merchant_id);
        $goods->hot_sort = $max_sort + 1;

        DB::beginTransaction();
        try {
            $goods->save();
            $cs_merchant->increment('hot_goods_count');
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new BaseResponseException('数据库错误');
        }



    }

    public static function getHotGoodsMaxSort($cs_merchant_id) {
        if ($cs_merchant_id<=0) {
            return false;
        }
        return CsGood::where('cs_merchant_id',$cs_merchant_id)->max('hot_sort');
    }

    /**
     * 修改排序
     * @param $cs_goods_id
     * @param $cs_merchant_id
     * @param string $type
     */
    public static function changeSort($cs_goods_id, $type = 'up')
    {
        if ($type == 'up'){
            $option = '>';
            $order = 'asc';
        }else{
            $option = '<';
            $order = 'desc';
        }

        $cs_goods = CsGood::findOrFail($cs_goods_id);

        $cs_merchant_id = $cs_goods->cs_merchant_id;
        $cs_goods_exchange = CsGood::where('cs_merchant_id', $cs_merchant_id)
            ->whereNotNull('hot_add_time')
            ->where('hot_sort', $option, $cs_goods->hot_sort)
            ->orderBy('hot_sort', $order)
            ->first();
        if (empty($cs_goods_exchange)){
            throw new BaseResponseException('交换位置的年货节商品不存在');
        }

        $item = $cs_goods_exchange->hot_sort;
        $cs_goods_exchange->hot_sort = $cs_goods->hot_sort;
        $cs_goods->hot_sort = $item;

        DB::beginTransaction();
        $res1 = $cs_goods->save();
        $res2 = $cs_goods_exchange->save();
        if ($res1 && $res2){
            DB::commit();
        }else{
            DB::rollBack();
            throw new BaseResponseException('交换位置失败');
        }
    }

    /**
     * 通过商户ID获取所有年货节
     * @param $list
     * @param $csMerchantId
     */
    public static function getHotSellListByCsMerchantId(&$list,$csMerchantId){
        $supermarket = CsMerchantService::getById($csMerchantId);
        if($supermarket->hot_status!=CsMerchant::HOT_STATUS_ON){
            //如果该商铺没有开启年货节活动，则退出
            return;
        }
        $hotSellGoodsList = CsGood::where('cs_merchant_id',$csMerchantId)
            ->where('hot_status',CsGood::HOT_STATUS_ON)
            ->where('status', CsGood::STATUS_ON)
            ->orderBy('hot_sort','desc')
            ->get();
        $hotSellArr = [
            'cat_name'  =>  CsActivityService::getHotSellDetail()->tag,
            'cat_id_level1' =>  -1,
            'cat_id_level2' =>  -1,
            'hot_sell'      =>  'on',
            'goods_list'    =>  $hotSellGoodsList->toArray(),
        ];
        $temp = array_shift($list);
        array_unshift($list,$temp,$hotSellArr);
    }

    public static function addMarketingInfo(&$list,$csMerchantId)
    {
        //$marketing_info = MarketingCurlService::getMerchantMarketing($csMerchantId);

        $params['type'] = 3;
        $params['merchant_id'] = $csMerchantId;
        $params['marketing_id'] = 1;

        $marketing_info = MarketingApi::getMerchantGoodsListStart($params);

        if (!empty($marketing_info['list']['data'])) {

                $marketingArr = [
                    'cat_name'  =>  '限时抢',
                    'cat_id_level1' =>  1,
                    'cat_id_level2' =>  0,
                    'hot_sell'      =>  'off',
                    'cat_type'      =>  self::CAT_TYPE_FLASH_SALE,
                    'goods_list'    =>  [],
                ];

            $temp = array_shift($list);
            array_unshift($list,$temp,$marketingArr);
        }
    }

    public static function addGroupBookCategories(&$list,$csMerchantId)
    {
        $groupBookGoods = CsGoodService::getGroupBookGoodsFromMarketing($csMerchantId);
        if (!$groupBookGoods) {
            return;
        }
        $groupBookArr = [
            'cat_name'  =>  '超值拼',
            'cat_id_level1' =>  1,
            'cat_id_level2' =>  0,
            'hot_sell'      =>  'off',
            'cat_type'      =>  self::CAT_TYPE_GROUP_BOOK,
            'goods_list'    =>  [],
        ];
        $temp = array_shift($list);
        array_unshift($list,$temp,$groupBookArr);
    }

    /**
     * 获取超市商户上架年货节商品数量
     * @param $cs_merchant_id
     * @return bool|int
     */
    public static function getHotCount($cs_merchant_id)
    {

        if ($cs_merchant_id <= 0 ) {
            return false;
        }

        return CsGood::where('cs_merchant_id',$cs_merchant_id)
            ->whereNotNull('hot_add_time')
            ->where('hot_status',CsGood::HOT_STATUS_ON)
            ->count();

    }

    /**
     * 通过商户ID获取年货节商品数量
     * @param $merchantId
     * @return int
     */
    public static function getHotNumByMerchantId($merchantId)
    {
        $num = CsGood::where('cs_merchant_id', $merchantId)
            ->whereNotNull('hot_add_time')
            ->count();

        return $num;
    }

    /**
     * 通过运营中心ID获取年货节商品数量
     * @param $operId
     * @return int
     */
    public static function getHotNumByOperId($operId)
    {
        $num = CsGood::where('oper_id', $operId)
            ->whereNotNull('hot_add_time')
            ->count();

        return $num;
    }

    /**
     * 通过id查看超市商品详情
     * @param $id
     * @return CsGood
     */
    public static function getById($id)
    {
        $detail = CsGood::find($id);
        if (empty($detail)) {
            throw new DataNotFoundException('该超市商品不存在');
        }
        return $detail;
    }

    /**
     * @param $id
     * @param $merchantId
     * @return mixed
     */
    public static function getByIdNMerchantIdToMarketing($id,$merchantId)
    {
        $query = CsGood::where('id', $id)->where('cs_merchant_id', $merchantId);
        return self::getChangeColumnName($query)->first();
    }

    /**
     * 通过IDS获取列表
     * @param $ids
     * @return CsGood[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getListByIdsToMarketing($ids)
    {
        $query = CsGood::whereIn('id',$ids);
        return self::getChangeColumnName($query)->get();
    }

    /**
     * @param $name
     * @param $merchantId
     * @return mixed
     */
    public static function getListByNameNMerchantIdToMarketing($name,$merchantId)
    {
        $query = CsGood::where('cs_merchant_id',$merchantId)
            ->where('goods_name','like','%'.$name.'%')
            ->where('hot_status','!=',CsGood::STATUS_ON)
            ->where('status',CsGood::STATUS_ON);
        return self::getChangeColumnName($query)->get();
    }

    /**
     * @param $query
     * @return mixed
     */
    public static function getChangeColumnName($query)
    {
        return $query->select('*')
        ->selectRaw('logo as thumb_url')
        ->selectRaw('detail_imgs as pic_list')
        ->selectRaw('"" as buy_info')
        ->selectRaw('summary as "desc"')
        ->selectRaw('"" as start_date')
        ->selectRaw('goods_name as name')
        ->selectRaw('"" as end_date');
    }

    /***
     * 获取营销端所需数据
     * @param $userId
     * @return mixed
     */
    public static function userGoodsList($userId)
    {
        $supermarket = CsMerchantService::getById($userId);
        if(!$supermarket){
            throw new BaseResponseException('超市用户不存在');
        }
        $query = CsGood::where('cs_merchant_id',$userId)
                    ->where('status',CsGood::STATUS_ON);
        return self::getChangeColumnName($query)->get();
    }

    /**
     * 通过商品名称关键字 查找所有商品
     * @param $name
     * @param bool $returnAll
     * @return CsGood[]|\Illuminate\Contracts\Pagination\LengthAwarePaginator|Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getListByName($name, $returnAll = false)
    {
        $query = CsGood::with('cs_merchant:id,name,signboard_name,province,city,area');
        $query->where('audit_status', CsGood::AUDIT_STATUS_SUCCESS)
            ->where('status', CsGood::STATUS_ON)
            ->where('goods_name', 'like', "%$name%")
            ->orderBy('sale_count_30d', 'desc');
        if ($returnAll) {
            return $query->get();
        }
        $list = $query->paginate();
        $list->each(function($item) {
            $goods = MarketingApi::getInActivityMarketingGoods($item->id, MarketingApi::MARKETING_GOODS_TYPE_CS_GOODS);
            if (!empty($goods)) {
                $item->marketing_id = $goods['marketing_id'];
                $item->discount_price = $goods['discount_price'];
            } else {
                $item->marketing_id = 0;
                $item->discount_price = 0;
            }
        });
        return $list;
    }

    /**
     * 通过商品主键ID 查找所有商品
     * @param $ids
     * @param bool $returnAll
     * @return CsGood[]|\Illuminate\Contracts\Pagination\LengthAwarePaginator|Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getListById($ids, $returnAll = false)
    {
        $query = CsGood::with('cs_merchant:id,name,signboard_name,province,city,area');
        $query->where('audit_status', CsGood::AUDIT_STATUS_SUCCESS)
            ->where('status', CsGood::STATUS_ON)
            ->whereIn('id',  $ids)
            ->orderBy('sale_count_30d', 'desc');
        if ($returnAll) {
            return $query->get();
        }
        $list = $query->paginate();
        $list->each(function($item) {
            $goods = MarketingApi::getInActivityMarketingGoods($item->id, MarketingApi::MARKETING_GOODS_TYPE_CS_GOODS);
            if (!empty($goods)) {
                $item->marketing_id = $goods['marketing_id'];
                $item->discount_price = $goods['discount_price'];
            } else {
                $item->marketing_id = 0;
                $item->discount_price = 0;
            }
        });
        return $list;
    }

    /**
     * 更新 最近30天销售数量
     * @param $id
     * @param $saleCount
     * @return CsGood
     */
    public static function updateSaleCount($id, $saleCount)
    {
        $csGoods = self::getById($id);
        $csGoods->sale_count_30d = $saleCount;
        $csGoods->save();

        return $csGoods;
    }

    /**
     * 添加超市商品库存
     * @param $id
     * @param $addStockNum
     * @return CsGood
     */
    public static function addStock($id, $addStockNum)
    {
        $goods = self::getById($id);
        if (empty($goods)) {
            throw new BaseResponseException('该超市商品不存在');
        }
        $goods->stock += $addStockNum;
        $goods->save();

        return $goods;
    }

    public static function getGroupBookGoods($merchantId, $page, $pageSize)
    {
        $groupBookGoods = self::getGroupBookGoodsFromMarketing($merchantId);
        if (!$groupBookGoods) {
            return [];
        }
        $goodsIds = array_column($groupBookGoods,'goods_id');
        $query = CsGood::where('status',CsGood::STATUS_ON)
            ->where('stock','>',0)
            ->whereIn('id',$goodsIds);
        $count = $query->count();
        $list = $query->get()->each(function($item) use ($groupBookGoods, $goodsIds) {
            $key = array_search($item->id, $goodsIds);
            GoodsService::insertGroupBookingData($item, $groupBookGoods[$key]);
            $item->group_book_sort = $item->group_booking->sort_number;
            $item->group_book_team_count = $item->group_booking->team_count;
        })
            ->sortByDesc('group_book_team_count')
            ->sortByDesc('group_book_sort')
            ->forPage($page, $pageSize)
            ->values()
            ->all();

        return ['list' => $list, 'total' => $count ];

    }

    public static function getGroupBookGoodsFromMarketing($merchantId)
    {
        $groupBookGoods = MarketingApi::getGroupBookingFromMarketing(3, 2, $merchantId);
        return $groupBookGoods;
    }



}
