<?php

namespace App\Modules\User;

use App\BaseModel;

/**
 * Class UserDevice
 * @package App\Modules\User
 *
 * @property integer user_id
 * @property integer user_type
 * @property integer device_type
 * @property string device_no
 */
class UserDevice extends BaseModel
{
    // 用户类型  1-用户 2-商户 3-运营中心 4-业务员 5-超市
    const USER_TYPE_USER = 1;
    const USER_TYPE_MERCHANT = 2;

    // 设备类型  1-Android,2-IOS,3-merchantAndroid,4-merchantIOS
    const DEVICE_TYPE_ANDROID = 1;
    const DEVICE_TYPE_IOS = 2;
    const DEVICE_TYPE_MERCHANT_ANDROID = 3;
    const DEVICE_TYPE_MERCHANT_IOS = 4;
}
