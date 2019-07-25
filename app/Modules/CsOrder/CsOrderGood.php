<?php

namespace App\Modules\CsOrder;

use App\BaseModel;
use App\Modules\Cs\CsGood;
use App\Modules\Order\Order;

/**
 * 购买超市商品
 * Class CsOrderGood
 * @package App\Modules\CsOrder
 * @property integer  id
 * @property integer  oper_id
 * @property integer cs_merchant_id
 * @property integer order_id
 * @property integer cs_goods_id
 * @property float price
 * @property float original_price
 * @property integer number
 * @property string goods_name
 * @property integer marketing_id
 * @property integer phase_id
 */

class CsOrderGood extends BaseModel
{
    const CS_HOT_ACTIVITY = 1;

    public function cs_goods()
    {
        return $this->belongsTo(CsGood::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
