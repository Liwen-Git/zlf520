<?php

namespace App\Modules\CsStatistics;

use App\BaseModel;
use App\Modules\Cs\CsGood;
use App\Modules\Cs\CsMerchant;
use App\Modules\Oper\Oper;

/**
 * Class CsStatisticsHotActGood
 * @package App\Modules\CsStatistics
 * @property string date
 * @property integer goods_id
 * @property integer merchant_id
 * @property integer oper_id
 * @property integer goods_type
 * @property float pay_price
 * @property float market_price
 * @property integer sale_num
 * @property float sale_total
 */

class CsStatisticsHotActGood extends BaseModel
{
    protected $guarded = [];

    public function csGoods()
    {
        return $this->belongsTo(CsGood::class, 'goods_id');
    }

    public function csMerchant()
    {
        return $this->belongsTo(CsMerchant::class, 'merchant_id');
    }

    public function oper()
    {
        return $this->belongsTo(Oper::class);
    }
}
