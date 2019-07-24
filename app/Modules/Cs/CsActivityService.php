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
use App\ResultCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class CsActivityService extends BaseService
{
    /**
     * 缓存爆款活动key
     */
    const HOT_SELL_ACTIVITY_KEY = 'hot_sell_detail';

    /**
     * 获取活动列表
     */
    public static function getList()
    {
        $list = CsActivities::get();

         return $list;
    }

    public static function updateStatus($id,$status)
    {
        $activity = CsActivities::where('id',$id)->first();

        if(!$activity){
            throw new BaseResponseException('数据不存在');
        }
        $activity->status = $status;

        if( !($activity->save()) ) {
            throw new BaseResponseException('更新失败', ResultCode::DB_INSERT_FAIL);
        }
        DataCacheService::removeHotSellCache();

    }


    public static function getActivity($id)
    {
        $activity = CsActivities::where('id',$id)->first();
        if($activity){
            $activity->logo = $activity->logo ? explode(',', $activity->logo) : [];
            $activity->pic_list =  $activity->pic_list ? explode(',', $activity->pic_list) : [];

            $activity->icon = $activity->icon ? explode(',', $activity->icon) : [];
            $activity->triangle_icon = $activity->triangle_icon ? explode(',', $activity->triangle_icon) : [];
            $activity->banner = $activity->banner ? explode(',', $activity->banner) : [];
            $activity->start_ad = $activity->start_ad ? explode(',', $activity->start_ad) : [];
            $activity->bottom_right_icon = $activity->bottom_right_icon ? explode(',', $activity->bottom_right_icon) : [];

        }
        return $activity;
    }

    public static function saveActivity(array $params = [])
    {

        $activity = CsActivities::where('id',$params['id'])->first();

        if(!$activity){
            throw new BaseResponseException('数据不存在');
        }

        $activity->title = $params['title'];
        $activity->logo = $params['logo'] ? implode(",",$params['logo']) : '';
        $activity->tag = $params['tag'];
        $activity->remark = $params['remark'];
        $activity->desc = $params['desc'];
        $activity->pic_list = $params['pic_list'] ? implode(",",$params['pic_list']) : '';
        $activity->icon = $params['icon'] ? implode(",",$params['icon']) : '';
        $activity->triangle_icon = $params['triangle_icon'] ? implode(",",$params['triangle_icon']) : '';
        $activity->banner = $params['banner'] ? implode(",",$params['banner']) : '';
        $activity->start_ad = $params['start_ad'] ? implode(",",$params['start_ad']) : '';
        $activity->bottom_right_icon = $params['bottom_right_icon'] ? implode(",",$params['bottom_right_icon']) : '';


        if( !($activity->save()) ) {
            throw new BaseResponseException('更新失败', ResultCode::DB_INSERT_FAIL);
        }
        DataCacheService::removeHotSellCache();
    }

    /**
     * 判断是否开启年货节活动
     */
    public static function isOpenHotSell()
    {
        $hotSell = self::getHotSellDetail();
        return !($hotSell) ? false : (($hotSell->status!=CsActivities::STATUS_ON) ? false : true);
    }

    public static function getHotSellDetail()
    {
        return DataCacheService::getHotSellDetail();
    }

    /**
     * 检查是否启用年货节活动
     */
    public static function checkEnableCsActivity(){
        $activity = CsActivities::where([['id',1],['status',1]])->first();
        return ($activity) ? true : false;
    }



    public static function getTopGoods()
    {

        $top_num = 5;

    }

    public static function getOtherGoods()
    {

    }


}
