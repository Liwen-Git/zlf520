<?php

namespace App\Http\Controllers\UserApp;

use App\Modules\Cs\CsActivityService;
use App\Modules\Cs\CsGoodService;
use App\Result;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CsActivityController extends Controller
{
    public function getHotSell()
    {
        $activity = CsActivityService::getHotSellDetail();
        return Result::success($activity);
    }

    public function getHotGoods()
    {

        $params['pageSize'] = request('pageSize',15);
        $data = CsGoodService::getHotGoods($params);
        $top_goods = CsGoodService::getTopGoods();
        return Result::success([
            'list' => $data->items(),
            'top_goods' => $top_goods,
            'total' => $data->total(),
        ]);
    }
}
