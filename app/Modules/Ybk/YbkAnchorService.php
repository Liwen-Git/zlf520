<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/20
 * Time: 9:02
 */
namespace App\Modules\Ybk;

use App\BaseService;
use App\Modules\Order\Order;

class YbkAnchorService extends BaseService
{
    public static function search($keyword)
    {

        if (empty($keyword) && !is_numeric($keyword)) {
            return '';
        }

        $query = YbkAnchor::select('*')
            ->where('nickname','like',"%{$keyword}%")
            ;
        $data = $query->paginate(100);
        $list = $data->items();
        $total = $data->total();

        return ['list' => $list, 'total' => $total];
    }

    public static function getByNumber($number) {
        return YbkAnchor::where('ybk_pk',$number)->first();
    }

    /**
     * 统计所有主播总订单金额
     */
    public static function orderStatistics() {

        YbkAnchor::select('*')
            ->chunk(1000, function ( $list ) {
                $list->each(function ($data) {

                    $data->order_amount = Order::where('ybk_anchor_id',$data->ybk_pk)
                        ->whereIn('status',[Order::STATUS_FINISHED])
                        ->sum('pay_price');

                    $data->statistics_time = date('Y-m-d H:i:s');
                    $data->save();
                });
            });
    }

    public static function getList(array $params = [])
    {

        $query = YbkAnchor::select('ybk_pk','order_amount')
            ->orderBy('order_amount','desc')
        ;

        $pageSize = array_get($params, 'pageSize',15);
        $data = $query->paginate($pageSize);

        return $data;


    }

}