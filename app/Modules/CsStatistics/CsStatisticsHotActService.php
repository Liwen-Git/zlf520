<?php

namespace App\Modules\CsStatistics;


use App\BaseService;
use App\Modules\Cs\CsGoodService;
use App\Modules\CsOrder\CsOrderGood;
use App\Modules\Order\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CsStatisticsHotActService extends BaseService
{

    /**
     * 年货节统计 获取商品统计列表
     * @param array $params
     * @param bool $withQuery
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|Builder
     */
    public static function getStaGoodsList(Array $params = [], bool $withQuery = false)
    {
        $startDate = array_get($params, 'startDate');
        $endDate = array_get($params, 'endDate');
        $goodsName = array_get($params, 'goodsName');
        $goodsType = array_get($params, 'goodsType');
        $merchantNameOrId = array_get($params, 'merchantNameOrId');
        $operNameOrId = array_get($params, 'operNameOrId');
        $orderColumn = array_get($params, 'orderColumn');
        $orderType = array_get($params, 'orderType');
        $pageSize = array_get($params, 'pageSize', 15);

        $query = CsStatisticsHotActGood::query();
        $query->with('csGoods:id,goods_name,price,market_price');
        $query->with('csMerchant:id,name');
        $query->with('oper:id,name');

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        if ($goodsName) {
            $query->whereHas('csGoods', function (Builder $query) use ($goodsName) {
                $query->where('goods_name', 'like', "%$goodsName%");
            });
        }

        if ($goodsType) {
            $query->where('goods_type', $goodsType);
        }

        if ($merchantNameOrId) {
            $query->whereHas('csMerchant', function (Builder $query) use ($merchantNameOrId) {
                if (is_numeric($merchantNameOrId)) {
                    $query->where('id', $merchantNameOrId);
                }else {
                    $query->where('name', 'like', "%$merchantNameOrId%");
                }
            });
        }

        if ($operNameOrId) {
            $query->whereHas('oper', function (Builder $query) use ($operNameOrId) {
                if (is_numeric($operNameOrId)) {
                    $query->where('id', $operNameOrId);
                }else {
                    $query->where('name', 'like', "%$operNameOrId%");
                }
            });
        }

        $orderColumn = $orderColumn ?: 'sale_total';
        $orderType = $orderType ?: 'descending';
        $query->orderBy($orderColumn, $orderType == 'descending' ? 'desc' : 'asc');

        if ($withQuery) {
            return  $query;
        }

        $data = $query->paginate($pageSize);

        return $data;
    }

    /**
     * 年货节统计 获取商户统计列表
     * @param array $params
     * @param bool $withQuery
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|Builder
     */
    public static function getStaMerchantList(Array $params = [], bool $withQuery = false)
    {
        $startDate = array_get($params, 'startDate');
        $endDate = array_get($params, 'endDate');
        $merchantNameOrId = array_get($params, 'merchantNameOrId');
        $operNameOrId = array_get($params, 'operNameOrId');
        $orderColumn = array_get($params, 'orderColumn');
        $orderType = array_get($params, 'orderType');
        $pageSize = array_get($params, 'pageSize', 15);

        $query = CsStatisticsHotActMerchant::query();
        $query->with('csMerchant:id,name');
        $query->with('oper:id,name');

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        if ($merchantNameOrId) {
            $query->whereHas('csMerchant', function (Builder $query) use ($merchantNameOrId) {
                if (is_numeric($merchantNameOrId)) {
                    $query->where('id', $merchantNameOrId);
                }else {
                    $query->where('name', 'like', "%$merchantNameOrId%");
                }
            });
        }

        if ($operNameOrId) {
            $query->whereHas('oper', function (Builder $query) use ($operNameOrId) {
                if (is_numeric($operNameOrId)) {
                    $query->where('id', $operNameOrId);
                }else {
                    $query->where('name', 'like', "%$operNameOrId%");
                }
            });
        }

        $orderColumn = $orderColumn ?: 'sale_total';
        $orderType = $orderType ?: 'descending';
        $query->orderBy($orderColumn, $orderType == 'descending' ? 'desc' : 'asc');

        if ($withQuery) {
            return  $query;
        }

        $data = $query->paginate($pageSize);

        return $data;
    }

    /**
     * 年货节统计 获取运营中心统计列表
     * @param array $params
     * @param bool $withQuery
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|Builder
     */
    public static function getStaOperList(Array $params = [], bool $withQuery = false)
    {
        $startDate = array_get($params, 'startDate');
        $endDate = array_get($params, 'endDate');
        $operNameOrId = array_get($params, 'operNameOrId');
        $orderColumn = array_get($params, 'orderColumn');
        $orderType = array_get($params, 'orderType');
        $pageSize = array_get($params, 'pageSize', 15);

        $query = CsStatisticsHotActOper::query();
        $query->with('oper:id,name');

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        if ($operNameOrId) {
            $query->whereHas('oper', function (Builder $query) use ($operNameOrId) {
                if (is_numeric($operNameOrId)) {
                    $query->where('id', $operNameOrId);
                }else {
                    $query->where('name', 'like', "%$operNameOrId%");
                }
            });
        }

        $orderColumn = $orderColumn ?: 'sale_total';
        $orderType = $orderType ?: 'descending';
        $query->orderBy($orderColumn, $orderType == 'descending' ? 'desc' : 'asc');

        if ($withQuery) {
            return  $query;
        }

        $data = $query->paginate($pageSize);

        return $data;
    }

    /**
     * 年货节活动 商品每日统计
     * @param $endTime
     */
    public static function staGoods($endTime)
    {
        $startTime = date('Y-m-d 00:00:00', strtotime($endTime));

        $query = CsOrderGood::select('cs_goods_id', DB::raw('sum(number) as sale_num, sum(price * number) as sale_total'));
        $query->with('cs_goods:id,price,market_price,hot_add_time,oper_id,cs_merchant_id');

        $query->where('activity_id', CsOrderGood::CS_HOT_ACTIVITY);

        $query->whereHas('order', function (Builder $query) use ($startTime, $endTime) {
            $query->where('type', Order::TYPE_SUPERMARKET)
                ->where('status', Order::STATUS_FINISHED)
                ->whereBetween('finish_time', [$startTime, $endTime]);
        });

        $query->whereHas('cs_goods', function (Builder $query) {
            $query->where('hot_add_time', '<>', null);
        });

        $query->groupBy('cs_goods_id');
        $query->orderBy('cs_goods_id');
        $query->chunk(1000, function($items) use ($endTime) {
            foreach ($items as $item) {
                $where['date'] =  date('Y-m-d', strtotime($endTime));
                $where['goods_id'] = $item->cs_goods_id;

                $row['merchant_id'] = $item->cs_goods->cs_merchant_id;
                $row['oper_id'] = $item->cs_goods->oper_id;
                $row['goods_type'] = Order::TYPE_SUPERMARKET;
                $row['pay_price'] = $item->cs_goods->price;
                $row['market_price'] = $item->cs_goods->market_price;
                $row['sale_num'] = $item->sale_num;
                $row['sale_total'] = $item->sale_total;

                (new CsStatisticsHotActGood())->updateOrCreate($where, $row);
            }
        });
    }

    /**
     * 年货节活动 商户每日统计
     * @param $endTime
     */
    public static function staMerchant($endTime)
    {
        $startTime = date('Y-m-d 00:00:00', strtotime($endTime));

        $query = CsOrderGood::select('cs_merchant_id', 'oper_id', DB::raw('sum(number) as sale_num, sum(price * number) as sale_total'));

        $query->where('activity_id', CsOrderGood::CS_HOT_ACTIVITY);

        $query->whereHas('order', function (Builder $query) use ($startTime, $endTime) {
            $query->where('type', Order::TYPE_SUPERMARKET)
                ->where('status', Order::STATUS_FINISHED)
                ->whereBetween('finish_time', [$startTime, $endTime]);
        });

        $query->whereHas('cs_goods', function (Builder $query) {
            $query->where('hot_add_time', '<>', null);
        });

        $query->groupBy(['cs_merchant_id', 'oper_id']);
        $query->orderBy('cs_merchant_id');

        $query->chunk(1000, function($items) use ($endTime) {
            foreach ($items as $item) {
                $where['date'] =  date('Y-m-d', strtotime($endTime));
                $where['merchant_id'] = $item->cs_merchant_id;

                $row['oper_id'] = $item->oper_id;
                $row['goods_num'] = CsGoodService::getHotNumByMerchantId($item->cs_merchant_id);
                $row['sale_num'] = $item->sale_num;
                $row['sale_total'] = $item->sale_total;

                (new CsStatisticsHotActMerchant())->updateOrCreate($where, $row);
            }
        });
    }

    /**
     * 年货节活动 运营中心每日统计
     * @param $endTime
     */
    public static function staOper($endTime)
    {
        $startTime = date('Y-m-d 00:00:00', strtotime($endTime));

        $query = CsOrderGood::select('oper_id', DB::raw('sum(number) as sale_num, sum(price * number) as sale_total'));

        $query->where('activity_id', CsOrderGood::CS_HOT_ACTIVITY);

        $query->whereHas('order', function (Builder $query) use ($startTime, $endTime) {
            $query->where('type', Order::TYPE_SUPERMARKET)
                ->where('status', Order::STATUS_FINISHED)
                ->whereBetween('finish_time', [$startTime, $endTime]);
        });

        $query->whereHas('cs_goods', function (Builder $query) {
            $query->where('hot_add_time', '<>', null);
        });

        $query->groupBy('oper_id');
        $query->orderBy('oper_id');

        $query->chunk(1000, function($items) use ($endTime) {
            foreach ($items as $item) {
                $where['date'] =  date('Y-m-d', strtotime($endTime));
                $where['oper_id'] = $item->oper_id;

                $row['goods_num'] = CsGoodService::getHotNumByOperId($item->oper_id);
                $row['sale_num'] = $item->sale_num;
                $row['sale_total'] = $item->sale_total;

                (new CsStatisticsHotActOper())->updateOrCreate($where, $row);
            }
        });
    }
}
