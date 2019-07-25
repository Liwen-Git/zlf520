<?php

namespace App\Modules\Order;

use App\BaseModel;

/**
 * Class OrderItem
 * @package App\Modules\Order
 *
 * @property number oper_id
 * @property number merchant_id
 * @property number order_id
 * @property number verify_code
 * @property number status
 */

class OrderItem extends BaseModel
{
    const STATUS_UN_VERIFY = 1;
    const STATUS_VERIFY = 2;
    const STATUS_REFUND = 3;

    /**
     * 生成核销码
     * @param $merchantId
     * @return int
     */
    public static function createVerifyCode($merchantId)
    {
        $verifyCode = rand(100000, 999999);
        if(OrderItem::where('verify_code', $verifyCode)
            ->where('merchant_id', $merchantId)
            ->first()){
            $verifyCode = self::createVerifyCode($merchantId);
        }
        return $verifyCode;
    }
}
