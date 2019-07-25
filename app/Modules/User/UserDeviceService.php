<?php

namespace App\Modules\User;

use App\BaseService;
use App\Exceptions\BaseResponseException;

class UserDeviceService extends BaseService
{
    /**
     * 创建或者更新 用户设备
     * @param $userId
     * @param $userType
     * @param $deviceType
     * @param $deviceNo
     * @return UserDevice
     */
    public static function addOrUpdate($userId, $userType, $deviceType, $deviceNo)
    {
        $deviceTypeArr = [UserDevice::DEVICE_TYPE_ANDROID, UserDevice::DEVICE_TYPE_IOS, UserDevice::DEVICE_TYPE_MERCHANT_ANDROID, UserDevice::DEVICE_TYPE_MERCHANT_IOS];
        if (!in_array($deviceType, $deviceTypeArr)) {
            throw new BaseResponseException('设备类型错误');
        }

        $userDevice = UserDevice::where('user_id', $userId)
            ->where('user_type', $userType)
            ->first();
        if (empty($userDevice)) {
            $userDevice = new UserDevice();
            $userDevice->user_id = $userId;
            $userDevice->user_type = $userType;
            $userDevice->device_type = $deviceType;
            $userDevice->device_no = $deviceNo;
        } else {
            $userDevice->device_type = $deviceType;
            $userDevice->device_no = $deviceNo;
        }
        $userDevice->save();

        return $userDevice;
    }

    /**
     * 退出登录 清除设备
     * @param $userId
     * @param $userType
     * @return UserDevice
     */
    public static function clearUserDevice($userId, $userType)
    {
        $userDevice = UserDevice::where('user_id', $userId)
            ->where('user_type', $userType)
            ->first();
        if (!empty($userDevice)) {
            $userDevice->device_type = 0;
            $userDevice->device_no = '';
            $userDevice->save();
        }

        return $userDevice;
    }

    /**
     * 获取用户设备信息
     * @param $userId
     * @param $userType
     * @return UserDevice
     */
    public static function getUserDevice($userId, $userType)
    {
        $userDevice = UserDevice::where('user_id', $userId)
            ->where('user_type', $userType)
            ->first();

        return $userDevice;
    }
}
