<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/16
 * Time: 21:01
 */

namespace App\Observers;


use App\Modules\Cs\CsGood;

class CsGoodsObserver
{

    public function saving(CsGood $csGood)
    {
        // todo 模型字段值有没有改变
        if ($csGood->getOriginal('price') != $csGood->price && $csGood->is_in_markting != 1) {
            $csGood->sort_price = $csGood->price;
        }
    }
}
