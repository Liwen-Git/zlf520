<?php
/**
 * Created by PhpStorm.
 * User: tim.tang
 * Date: 2018/10/12/012
 * Time: 15:11
 */

namespace App;

use App\Modules\Cs\CsActivities;
use App\Modules\Cs\CsMerchant;
use App\Modules\Cs\CsMerchantCategoryService;
use App\Modules\Cs\CsPlatformCategoryService;
use App\Modules\Merchant\Merchant;
use App\Modules\Merchant\MerchantAccount;
use App\Modules\Oper\Oper;
use Illuminate\Support\Facades\Cache;
use test\Mockery\Fixtures\EmptyTestCaseV5;

class DataCacheService extends BaseService
{
    const REDIS_KEY_OPER = 'oper:id:';
    const REDIS_KEY_MERCHANT = 'merchant:id:';//商户信息缓存键值
    const REDIS_KEY_CS_MERCHANT = 'cs_merchant:id:';//超市商户信息缓存键值
    const REDIS_KEY_PLATFORM_CATS = 'common_data:platform_cats';//平台分类缓存
    const REDIS_KEY_PLATFORM_CATS_USEFUL = 'common_data:platform_cats_useful';//平台可用分类缓存
    const REDIS_KEY_CS_MERCHANT_CATS = 'common_data:merchant_cats:';//商户分类缓存
    const HOT_SELL_ACTIVITY_KEY = 'hot_sell_detail';    // 缓存爆款活动key
    const LOGIN_CACHE_ADMIN = 'login_cache:admin:';    // 登录token缓存
    const LOGIN_CACHE_MERCHANT = 'login_cache:merchant:';    // 登录token缓存
    const LOGIN_CACHE_CS = 'login_cache:cs:';    // 登录token缓存
    const LOGIN_CACHE_OPER = 'login_cache:oper:';    // 登录token缓存

    public static function setLoginCache(string $type,$data)
    {

        $data = $data->toArray();
        if (empty($data['token'])) {
            return false;
        }
        $cache_key = '';
        switch ($type) {
            case 'admin':
                $cache_key = self::LOGIN_CACHE_ADMIN . $data['token'];
                break;
            case 'merchant':
                if ($data['type'] == MerchantAccount::TYPE_NORMAL) {
                    $cache_key = self::LOGIN_CACHE_MERCHANT . $data['token'];
                } else {
                    $cache_key = self::LOGIN_CACHE_CS . $data['token'];
                }
                break;
            case 'oper':
                $cache_key = self::LOGIN_CACHE_OPER . $data['token'];
                break;
            default:
                break;

        }
        return Cache::store('redis')->set($cache_key, json_encode($data),86400);
    }

    public static function getLoginCache($type, $token)
    {
        $data = '';
        if (empty($token)) {
            return false;
        }
        switch ($type) {
            case 'admin':
                $cache_key = self::LOGIN_CACHE_ADMIN . $token;
                break;
            case 'merchant':

                $cache_key = self::LOGIN_CACHE_MERCHANT . $token;
                break;
            case 'cs':
                $cache_key = self::LOGIN_CACHE_CS . $token;
                break;
            case 'oper':
                $cache_key = self::LOGIN_CACHE_OPER . $token;
                break;

            default:
                break;

        }

        $data = Cache::store('redis')->get($cache_key);

        if (!empty($data)) {
            $data = json_decode($data,true);
        }
        return $data;
    }

    public static function delLoginCache($type, $data)
    {
        if (empty($data)) {
            return false;
        }
        $data = $data->toArray();
        if (empty($data['token'])) {
            return false;
        }
        $cache_key = '';
        switch ($type) {
            case 'admin':
                $cache_key = self::LOGIN_CACHE_ADMIN . $data['token'];
                break;
            case 'merchant':
                if ($data['type'] == MerchantAccount::TYPE_NORMAL) {
                    $cache_key = self::LOGIN_CACHE_MERCHANT . $data['token'];
                } else {
                    $cache_key = self::LOGIN_CACHE_CS . $data['token'];
                }
                break;
            case 'oper':
                $cache_key = self::LOGIN_CACHE_OPER . $data['token'];
                break;
            default:
                break;

        }
        return Cache::store('redis')->forget($cache_key);
    }

    /**
     * 获取运营中心详情缓存
     * @param $id
     * @return Merchant|mixed
     */
    public static function getOperDetail($id)
    {
        $cache_key = self::REDIS_KEY_OPER . $id;
        $data = Cache::store('redis')->get($cache_key);
        if (empty($data)) {
            $data = Oper::findOrFail($id,['id','name','contact_qq','contact_wechat','contact_mobile']);
            Cache::store('redis')->forever($cache_key, $data);
        }
        return $data;
    }

    /**
     * 删除运营中心详情缓存
     * @param array $ids
     */
    public static function delOperDetail($ids = [])
    {
        if ($ids) {
            foreach ($ids as $id) {
                $cache_key = self::REDIS_KEY_OPER . $id;
                Cache::store('redis')->forget($cache_key);
            }
        }
    }

    /**
     * 获取商户详情缓存
     * @param $id
     * @return Merchant|mixed
     */
    public static function getMerchantDetail($id)
    {
        $cache_key = self::REDIS_KEY_MERCHANT . $id;
        $data = Cache::store('redis')->get($cache_key);
        if (empty($data)) {
            $data = Merchant::findOrFail($id);
            Cache::store('redis')->forever($cache_key, $data);
        }
        return $data;
    }

    /**
     * 删除商户详情缓存
     * @param array $ids
     */
    public static function delMerchantDetail($ids = [])
    {
        if ($ids) {
            foreach ($ids as $id) {
                $cache_key = self::REDIS_KEY_MERCHANT . $id;
                Cache::store('redis')->forget($cache_key);
            }
        }
    }

    /**
     * 获取平台分类缓存
     * @return array|mixed
     */
    public static function getPlatformCats()
    {
        $data = Cache::store('redis')->get(self::REDIS_KEY_PLATFORM_CATS);
        if (empty($data)) {
            $data = CsPlatformCategoryService::getAllIdName();
            if (!empty($data)) {
                Cache::store('redis')->forever(self::REDIS_KEY_PLATFORM_CATS, json_encode($data));
            }
        } else {
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 删除平台分类缓存
     */
    public static function delPlatformCats()
    {
        Cache::store('redis')->forget(self::REDIS_KEY_PLATFORM_CATS);
    }

    /**
     * 获取平台分类缓存
     * @return array|mixed
     */
    public static function getPlatformCatsUseful()
    {
        $data = Cache::store('redis')->get(self::REDIS_KEY_PLATFORM_CATS_USEFUL);
        if (empty($data)) {
            $data = CsPlatformCategoryService::getAllIdName(1);
            if (!empty($data)) {
                Cache::store('redis')->forever(self::REDIS_KEY_PLATFORM_CATS_USEFUL, json_encode($data));
            }
        } else {
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 删除平台分类缓存
     */
    public static function delPlatformCatsUseful()
    {
        Cache::store('redis')->forget(self::REDIS_KEY_PLATFORM_CATS_USEFUL);
    }

    /**
     * 获取商户分类数据
     * @param $cs_merchant_id
     * @return array|\Illuminate\Support\Collection|mixed|string
     */
    public static function getCsMerchantCats($cs_merchant_id)
    {
        if ($cs_merchant_id <= 0) {
            return '';
        }
        $cache_key = self::REDIS_KEY_CS_MERCHANT_CATS . $cs_merchant_id;
        $data = Cache::store('redis')->get($cache_key);
        if (empty($data)) {
            $data = CsMerchantCategoryService::getTree($cs_merchant_id);
            if (!empty($data)) {
                Cache::store('redis')->forever($cache_key, json_encode($data));
            }
        } else {
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 删除平台分类缓存
     * @param $cs_merchant_id
     * @return bool|string
     */
    public static function delCsMerchantCats($cs_merchant_id)
    {
        if ($cs_merchant_id <= 0) {
            return '';
        }
        $cache_key = self::REDIS_KEY_CS_MERCHANT_CATS . $cs_merchant_id;
        return Cache::store('redis')->forget($cache_key);
    }

    /**
     * 删除所有商户分类缓存
     * @return bool
     */
    public static function delAllCsMerchantCats()
    {
        $cache_key = self::REDIS_KEY_CS_MERCHANT_CATS . '*';
        return Cache::store('redis')->forget($cache_key);
    }


    /**
     * 获取商户详情缓存
     * @param $id
     * @return Merchant|mixed
     */
    public static function getCsMerchantDetail($id)
    {
        $cache_key = self::REDIS_KEY_CS_MERCHANT . $id;
        $data = Cache::store('redis')->get($cache_key);
        if (empty($data)) {
            $data = CsMerchant::findOrFail($id);
            Cache::store('redis')->forever($cache_key, $data);
        }
        return $data;
    }

    /**
     * 删除商户详情缓存
     * @param array $ids
     */
    public static function delCsMerchantDetail($ids = [])
    {
        if ($ids) {
            foreach ($ids as $id) {
                $cache_key = self::REDIS_KEY_CS_MERCHANT . $id;
                Cache::store('redis')->forget($cache_key);
            }
        }
    }

    /**
     * 获取爆款活动详情缓存
     * @return CsActivities|mixed
     */
    public static function getHotSellDetail()
    {
        $active = Cache::store('redis')->get(self::HOT_SELL_ACTIVITY_KEY);
        if (empty($active)) {
            $active = CsActivities::findOrFail(1);
            Cache::store('redis')->forever(self::HOT_SELL_ACTIVITY_KEY, $active);
        }
        $active->banner = empty($active->banner) ? [] : explode(',',$active->banner);
        return $active;
    }

    /**
     * 清空爆款活动详情缓存
     */
    public static function removeHotSellCache(){
        Cache::store('redis')->forget(self::HOT_SELL_ACTIVITY_KEY);
    }

}