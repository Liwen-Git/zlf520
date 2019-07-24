<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AdminOrderExport;
use App\Exports\PlatformTradeRecordsExport;
use App\Http\Controllers\Controller;
use App\Modules\Dishes\DishesItem;
use App\Modules\Merchant\Merchant;
use App\Modules\Merchant\MerchantService;
use App\Modules\Oper\Oper;
use App\Modules\Oper\OperService;
use App\Modules\Order\Order;
use App\Modules\Order\OrderService;
use App\Modules\Payment\Payment;
use App\Modules\Platform\PlatformTradeRecordService;
use App\Result;
use App\Support\CsvExporter;

class OrderController extends Controller
{
    private static function getListQuery($withQuery = false)
    {
        $orderNo = request('orderNo');
        $mobile = request('mobile');
        $oper_id = request('oper_id');
        $merchantId = request('merchantId');
        $timeType = request('timeType', 'payTime');
        $startTime = request('startTime');
        $endTime = request('endTime');
        $status = request('status');
        $type = request('type');
        $merchantType = request('merchantType', Order::MERCHANT_TYPE_NORMAL);
        $marketingId = request('marketingId');

        if($timeType == 'payTime'){
            $startPayTime = $startTime;
            $endPayTime = $endTime;
        } elseif ($timeType == 'createdTime') {
            $startCreatedTime = $startTime;
            $endCreatedTime = $endTime;
        }else {
            $startFinishTime = $startTime;
            $endFinishTime = $endTime;
        }

        $data = OrderService::getList([
            'operId' => $oper_id,
            'merchantId' => $merchantId,
            'orderNo' => $orderNo,
            'notifyMobile' => $mobile,
            'startPayTime' => $startPayTime ?? null,
            'endPayTime' => $endPayTime ?? null,
            'startFinishTime' => $startFinishTime ?? null,
            'endFinishTime' => $endFinishTime ?? null,
            'startCreatedAt' => $startCreatedTime ?? null,
            'endCreatedAt' => $endCreatedTime ?? null,
            'status' => $status,
            'type' => $type,
            'merchantType' => $merchantType,
            'marketingId' => $marketingId,
            'platform_only' => request('platform_only')=='true',
            'from_saas' => 1
        ], $withQuery);

        return $data;
    }

    public function getList()
    {
        $data = self::getListQuery();

        return Result::success([
            'list' => $data->items(),
            'total' => $data->total(),
        ]);
    }

    public function getOptions()
    {
        $opers = OperService::allOpers();
        $merchants = MerchantService::getAllNames([]);
        $list['opers'] = $opers;
        $list['merchants'] = $merchants->toArray();
        return Result::success([
            'list' => $list
        ]);
    }

    /**
     * 导出订单
     */
    public function export()
    {
        $query = self::getListQuery(true);

        $opers = Oper::select('id', 'name')->pluck('name', 'id');
        CsvExporter::download($query, [
            'id' => 'ID',
            'oper_name' => '所属运营中心',
            'merchant_name' => '所属商户',
            'pay_time' => '支付时间',
            'order_no' => '订单号',
            'type' => '订单类型',
            'goods_name' => '商品名称',
            'pay_price' => '总价 (元)',
            'status' => '订单状态',
            'marketing_id' => '活动类型',
            'pay_type_name' => '支付方式',
            'created_at' => '创建时间',
            'finish_time' => '核销时间',
            'notify_mobile' => '手机号',
            'remark' => '备注',
        ], '订单列表', function($item) use ($opers){
            $item->merchant_name = MerchantService::getNameById($item->merchant_id);
            $item->oper_name = $opers[$item->oper_id];
            $item->pay_type_name = Order::getPayTypeText($item->pay_type);
            $dishesItems = [];
            if ($item->type == 3){
                $dishesItems = DishesItem::where('dishes_id', $item->dishes_id)->get();
            }
            $item->goods_name = Order::getGoodsNameText($item->type,$dishesItems,$item->goods_name);
            $item->type = Order::getTypeText($item->type);
            $item->status = Order::getStatusText($item->status);
            $item->marketing_id = Order::getMarketingText($item->marketing_id);
            return $item;
        });
    }

    public function csExport()
    {
        $query = self::getListQuery(true);

        $fileName = '超市订单列表';
        header('Content-Type: application/vnd.ms-execl');
        header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');

        $fp = fopen('php://output', 'a');
        $title = ['支付时间', '订单号', '历时', '订单类型', '商品名称', '总价（元）', '手机号码', '订单状态', '活动类型', '发货方式', '商户名称', '运营中心名称', '收货省', '市', '区', '收货地址', '联系人', '联系方式', '备注','快递公司','快递单号'];
        foreach ($title as $key => $value) {
            $title[$key] = iconv('UTF-8', 'GBK', $value);
        }
        fputcsv($fp, $title);

        $query->chunk(1000, function ($data) use ($fp) {
            foreach ($data as $key => $value) {
                $item = [];

                $item['pay_time'] = $value['pay_time'];
                $item['order_no'] = $value['order_no'];
                if (in_array($value['status'], [Order::STATUS_UNDELIVERED, Order::STATUS_NOT_TAKE_BY_SELF, Order::STATUS_DELIVERED])) {
                    $item['take_time'] = OrderService::formatTime(time(), strtotime($value['pay_time']));
                } elseif ($value['status'] == Order::STATUS_FINISHED) {
                    $item['take_time'] = OrderService::formatTime(strtotime($value['finish_time']), strtotime($value['pay_time']));
                } elseif ($value['status'] == Order::STATUS_REFUNDED) {
                    $item['take_time'] = OrderService::formatTime(strtotime($value['refund_time']), strtotime($value['pay_time']));
                } else {
                    $item['take_time'] = '';
                }
                $item['type'] = Order::getTypeText($value['type']);
                $item['goods_name'] = Order::getAllGoodsName($value['type'], $value['csOrderGoods']);
                $item['pay_price'] = $value['pay_price'];
                $item['notify_mobile'] = $value['notify_mobile'];
                $item['status'] = Order::getStatusText($value['status']);
                $item['marketing_id'] = Order::getMarketingText($value['marketing_id']);
                $item['deliver_type'] = Order::getDeliverTypeText($value['deliver_type']);
                $item['merchant_name'] = $value['merchant_name'];
                $item['oper_name'] = $value['oper']['name'];
                $expressAddress = json_decode($value['express_address']);
                $item['province'] = isset($expressAddress->province) ? $expressAddress->province : '';
                $item['city'] = isset($expressAddress->city) ? $expressAddress->city : '';
                $item['area'] = isset($expressAddress->area) ? $expressAddress->area : '';
                $item['address'] = isset($expressAddress->address) ? $expressAddress->address : '';
                $item['contacts'] = isset($expressAddress->contacts) ? $expressAddress->contacts : '';
                $item['contact_phone'] = isset($expressAddress->contact_phone) ? $expressAddress->contact_phone : '';
                $item['remark'] = $value['remark'];
                $item['express_company'] = $value['express_company'];
                $item['express_no'] = '#'.$value['express_no'];

                foreach ($item as $k => $v) {
                    $item[$k] = mb_convert_encoding($v, 'GBK', 'UTF-8');
                }
                fputcsv($fp, $item);
            }
            ob_flush();
            flush();
        });
    }

    public function platformTradeRecords()
    {

        $params = [
            'order_no' => request('order_no'),
            'trade_no' => request('trade_no'),
            'oper_id' => request('oper_id'),
            'merchant_id' => request('merchant_id'),
            'startTime' => request('startTime'),
            'endTime' => request('endTime'),
            'user_id' => request('user_id'),
            'merchant_type' => request('merchant_type'),
            'type' => request('type'),
        ];

        $data = PlatformTradeRecordService::getList($params);

        return Result::success([
            'list' => $data->items(),
            'total' => $data->total(),
        ]);
    }

    public function recordDetail()
    {
        $this->validate(request(), [
            'id' => 'required|integer|min:1'
        ]);

    }

    public function platformTradeRecordsExport()
    {

        $params = [
            'order_no' => request('order_no'),
            'trade_no' => request('trade_no'),
            'oper_id' => request('oper_id'),
            'merchant_id' => request('merchant_id'),
            'startTime' => request('startTime'),
            'endTime' => request('endTime'),
            'user_id' => request('user_id'),
            'merchant_type' => request('merchant_type'),
            'type' => request('type'),
        ];

        $data = PlatformTradeRecordService::getList($params,true);
        return (new PlatformTradeRecordsExport($data, $params))->download(' 平台交易记录.xlsx');
    }

    /**
     * 获取未发货超市订单的数量
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUndeliveredNum()
    {
        $params = [
            'status' => Order::STATUS_UNDELIVERED,
            'merchantType' => Order::MERCHANT_TYPE_SUPERMARKET,
        ];
        $query = OrderService::getList($params, true);
        $count = $query->count();

        return Result::success([
            'total' => $count,
        ]);
    }

    public function manualRefund()
    {
        $this->validate(request(),[
            'order_id'  =>  'required|integer|min:1'
        ]);
        return OrderService::manualRefund(request('order_id'));
    }
}