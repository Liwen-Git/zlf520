<?php

namespace App\Modules\CsStatistics;

use App\BaseModel;
use App\Modules\Cs\CsMerchant;
use App\Modules\Oper\Oper;

/**
 * Class CsStatisticsHotActMerchant
 * @package App\Modules\CsStatistics
 * @property string date
 * @property integer merchant_id
 * @property integer oper_id
 * @property integer goods_num
 * @property integer sale_num
 * @property float sale_total
 */

class CsStatisticsHotActMerchant extends BaseModel
{
    protected $guarded = [];

    public function csMerchant()
    {
        return $this->belongsTo(CsMerchant::class, 'merchant_id');
    }

    public function oper()
    {
        return $this->belongsTo(Oper::class);
    }
}
