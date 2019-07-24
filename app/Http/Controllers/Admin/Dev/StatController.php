<?php
/**
 * Created by PhpStorm.
 * User: 57458
 * Date: 2019/1/5
 * Time: 22:16
 */

namespace App\Http\Controllers\Admin\Dev;


use App\Http\Controllers\Controller;
use App\Modules\Cs\CsMerchant;
use App\Modules\Merchant\Merchant;
use App\Modules\Oper\Oper;
use App\Modules\Order\Order;
use App\Modules\User\User;
use App\Result;

class StatController extends Controller
{

    public function summary()
    {
        $start = request('start');
        $end = request('end');
        // 用户总数量
        $userQuery = User::query();
        $orderQuery = Order::query();
        $orderFinishQuery = Order::query();
        $merchantQuery = Merchant::query();
        $merchantAuditQuery = Merchant::query();
        $csMerchantQuery = CsMerchant::query();
        $csMerchantAuditQuery = CsMerchant::query();
        $operQuery = Oper::query();
        if($start && $start){
            $userQuery->whereBetween('created_at', [$start, $end]);
            $orderQuery->whereBetween('created_at', [$start, $end]);
            $orderFinishQuery->whereBetween('finish_time', [$start, $end]);
            $merchantQuery->whereBetween('created_at', [$start, $end]);
            $merchantAuditQuery->whereBetween('first_active_time', [$start, $end]);
            $csMerchantQuery->whereBetween('created_at', [$start, $end]);
            $csMerchantAuditQuery->whereBetween('first_active_time', [$start, $end]);
            $operQuery->whereBetween('created_at', [$start, $end]);

        }else if($start){
            $userQuery->where('created_at', '>', $start);
            $orderQuery->where('created_at', '>', $start);
            $orderFinishQuery->where('finish_time', '>', $start);
            $merchantQuery->where('created_at', '>', $start);
            $merchantAuditQuery->where('first_active_time', '>', $start);
            $csMerchantQuery->where('created_at', '>', $start);
            $csMerchantAuditQuery->where('first_active_time', '>', $start);
            $operQuery->where('created_at', '>', $start);
        }else if($end){
            $userQuery->where('created_at', '<', $end);
            $orderQuery->where('created_at', '<', $end);
            $orderFinishQuery->where('finish_time', '<', $end);
            $merchantQuery->where('created_at', '<', $end);
            $merchantAuditQuery->where('first_active_time', '<', $end);
            $csMerchantQuery->where('created_at', '<', $end);
            $csMerchantAuditQuery->where('first_active_time', '<', $end);
            $operQuery->where('created_at', '<', $end);
        }

        $userCount = $userQuery->count('id');

        // 订单
        $orderPaidCount = $orderQuery->whereNotIn('status', [
            Order::STATUS_UN_PAY,
            Order::STATUS_CANCEL,
            Order::STATUS_CLOSED,
        ])->count('id');
        $orderUnrefundCount = $orderQuery->whereNotIn('status', [
            Order::STATUS_UN_PAY,
            Order::STATUS_CANCEL,
            Order::STATUS_CLOSED,
            Order::STATUS_REFUNDING,
            Order::STATUS_REFUNDED,
        ])->count('id');
        $orderFinishCount = $orderFinishQuery->where('status', Order::STATUS_FINISHED)->count('id');
        $orderPaidAmount = $orderQuery->whereNotIn('status', [
            Order::STATUS_UN_PAY,
            Order::STATUS_CANCEL,
            Order::STATUS_CLOSED,
        ])->sum('pay_price');
        $orderUnrefundAmount = $orderQuery->whereNotIn('status', [
            Order::STATUS_UN_PAY,
            Order::STATUS_CANCEL,
            Order::STATUS_CLOSED,
            Order::STATUS_REFUNDING,
            Order::STATUS_REFUNDED,
        ])->sum('pay_price');
        $orderFinishAmount = $orderFinishQuery->where('status', Order::STATUS_FINISHED)->sum('pay_price');

        // 商户
        $merchantCount = $merchantQuery->where('is_pilot', 0)->count('id');
        $merchantEnabledCount = $merchantQuery->where('is_pilot', 0)->where('status', 1)->count('id');
        $merchantAuditCount = $merchantAuditQuery->where('is_pilot', 0)->whereIn('audit_status', [1, 3])->count('id');
        $merchantEnabledAuditCount = $merchantAuditQuery->where('is_pilot', 0)->where('status', 1)->whereIn('audit_status', [1, 3])->count('id');
        $pilotMerchantCount = $merchantQuery->where('is_pilot', 1)->count('id');
        $pilotMerchantEnabledCount = $merchantQuery->where('is_pilot', 1)->where('status', 1)->count('id');
        $pilotMerchantAuditCount = $merchantAuditQuery->where('is_pilot', 1)->whereIn('audit_status', [1, 3])->count('id');
        $pilotMerchantEnabledAuditCount = $merchantAuditQuery->where('is_pilot', 1)->where('status', 1)->whereIn('audit_status', [1, 3])->count('id');
        // 超市
        $csMerchantCount = $csMerchantQuery->count('id');
        $csMerchantEnabledCount = $csMerchantQuery->where('status', 1)->count('id');
        $csMerchantAuditCount = $csMerchantQuery->whereIn('audit_status', [1, 3])->count('id');
        $csMerchantEnabledAuditCount = $csMerchantQuery->where('status', 1)->whereIn('audit_status', [1, 3])->count('id');

        // 运营中心
        $operCount = $operQuery->count('id');

        return Result::success([
            'userCount' => $userCount,
            'orderPaidCount' => $orderPaidCount,
            'orderUnrefundCount' => $orderUnrefundCount,
            'orderFinishCount' => $orderFinishCount,
            'orderPaidAmount' => $orderPaidAmount,
            'orderUnrefundAmount' => $orderUnrefundAmount,
            'orderFinishAmount' => $orderFinishAmount,
            'merchantCount' => $merchantCount,
            'merchantEnabledCount' => $merchantEnabledCount,
            'merchantAuditCount' => $merchantAuditCount,
            'merchantEnabledAuditCount' => $merchantEnabledAuditCount,
            'pilotMerchantCount' => $pilotMerchantCount,
            'pilotMerchantEnabledCount' => $pilotMerchantEnabledCount,
            'pilotMerchantAuditCount' => $pilotMerchantAuditCount,
            'pilotMerchantEnabledAuditCount' => $pilotMerchantEnabledAuditCount,
            'csMerchantCount' => $csMerchantCount,
            'csMerchantEnabledCount' => $csMerchantEnabledCount,
            'csMerchantAuditCount' => $csMerchantAuditCount,
            'csMerchantEnabledAuditCount' => $csMerchantEnabledAuditCount,
            'operCount' => $operCount,
        ]);
    }
}