<?php

namespace App\Modules\CsStatistics;

use App\BaseModel;
use App\Modules\Oper\Oper;

/**
 * Class CsStatisticsHotActOper
 * @package App\Modules\CsStatistics
 * @property string date
 * @property integer oper_id
 * @property integer goods_num
 * @property integer sale_num
 * @property float sale_total
 */

class CsStatisticsHotActOper extends BaseModel
{
    protected $guarded = [];

    public function oper()
    {
        return $this->belongsTo(Oper::class);
    }
}
