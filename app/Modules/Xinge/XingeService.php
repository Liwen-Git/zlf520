<?php

namespace App\Modules\Xinge;

use App\BaseService;
use App\Exceptions\BaseResponseException;
use App\Modules\User\UserDevice;
use App\Support\Xinge\Xinge;
use Illuminate\Support\Facades\Log;

class XingeService extends BaseService
{
    /**
     * 通过token推送
     * @param $app
     * @param $appType
     * @param $token
     * @param $title
     * @param $content
     * @param string $subtitle
     * @param array $custom
     * @return array|mixed
     */
    public static function pushByToken($app, $appType, $token, $title, $content, $subtitle = '', $custom = [])
    {
        $xinge = new Xinge($app);
        if ($appType == UserDevice::DEVICE_TYPE_ANDROID || $appType == UserDevice::DEVICE_TYPE_MERCHANT_ANDROID) {
            $ret = $xinge->androidPushByToken($token, $title, $content, $custom);
        } elseif ($appType == UserDevice::DEVICE_TYPE_IOS || $appType == UserDevice::DEVICE_TYPE_MERCHANT_IOS) {
            $ret = $xinge->iosPushByToken($token, $title, $content, $subtitle, $custom, config('xinge.environment'));
        } else {
            throw new BaseResponseException('app类型错误');
        }
        if ($ret['ret_code'] != 0) {
            Log::info('推送失败 by token', compact('ret', 'app', 'appType', 'token', 'title', 'content'));
        }

        return $ret;
    }

    /**
     * 通过账号推送
     * @param $app
     * @param $appType
     * @param $account
     * @param $title
     * @param $content
     * @param string $subtitle
     * @param array $custom
     * @return bool
     */
    public static function pushByAccount($app, $appType, $account, $title, $content, $subtitle = '', $custom = [])
    {
        $xinge = new Xinge($app);
        if ($appType == UserDevice::DEVICE_TYPE_ANDROID || $appType == UserDevice::DEVICE_TYPE_MERCHANT_ANDROID) {
            $ret = $xinge->androidPushByAccount($account, $title, $content, $custom);
        } elseif ($appType == UserDevice::DEVICE_TYPE_IOS || $appType == UserDevice::DEVICE_TYPE_MERCHANT_IOS) {
            $ret = $xinge->iosPushByAccount($account, $title, $content, $subtitle, $custom, config('xinge.environment'));
        } else {
            throw new BaseResponseException('app类型错误');
        }

        if ($ret['ret_code'] != 0) {
            Log::info('推送失败 by account', compact('ret', 'app', 'appType', 'account', 'title', 'content'));
        }

        return $ret;
    }

    /**
     * 推送全部
     * @param $app
     * @param $appType
     * @param $title
     * @param $content
     * @param string $subtitle
     * @param array $custom
     * @return bool
     */
    public static function pushAll($app, $appType, $title, $content, $subtitle = '', $custom = [])
    {
        $xinge = new Xinge($app);
        if ($appType == UserDevice::DEVICE_TYPE_ANDROID || $appType == UserDevice::DEVICE_TYPE_MERCHANT_ANDROID) {
            $xinge->androidPushAll($title, $content, $custom);
        } elseif ($appType == UserDevice::DEVICE_TYPE_IOS || $appType == UserDevice::DEVICE_TYPE_MERCHANT_IOS) {
            $xinge->iosPushAll($title, $content, $subtitle, $custom, config('xinge.environment'));
        } else {
            throw new BaseResponseException('app类型错误');
        }

        return true;
    }
}