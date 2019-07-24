<?php
/**
 * 结算数据【新】
 */

namespace App\Http\Controllers\Admin;


use App\Exceptions\BaseResponseException;
use App\Exports\SettlementPlatformExport;
use App\Exports\SettlementPlatformOrdersExport;
use App\Http\Controllers\Controller;
use App\Modules\Cs\CsMerchant;
use App\Modules\CsOrder\CsOrderGood;
use App\Modules\CsOrder\CsOrderGoodService;
use App\Modules\Dishes\DishesItem;
use App\Modules\Goods\Goods;
use App\Modules\Merchant\Merchant;
use App\Modules\Oper\Oper;
use App\Modules\Order\Order;
use App\Modules\Order\OrderService;
use App\Modules\Settlement\SettlementPlatform;
use App\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Modules\Settlement\SettlementPlatformService;
use Illuminate\Support\Facades\Request;

class SettlementPlatformController extends Controller
{

    /**
     * 获取结算列表【新】
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getList()
    {
        $merchant_name = request('merchant_name');
        $oper_name = request('oper_name');
        $merchant_id = request('merchant_id');
        $startDate = request('startDate');
        $endDate = request('endDate');
        $status = request('status');
        $settlementCycleType = request('settlement_cycle_type');
        $isAutoSettlement = request('is_auto_settlement');
        $settlementNo = request('settlement_no');
        $manualGen = request('manual_gen','false') == 'false' ? false : true;

        $uri = request()->getRequestUri();
        // 商户类型
        $merchantType = (strpos($uri,'csPlatforms')) ? SettlementPlatform::MERCHANT_TYPE_CS : ((request('merchant_type')) ? request('merchant_type') : SettlementPlatform::MERCHANT_TYPE_NORMAL);
        $startTime = microtime(true);
        $data = SettlementPlatformService::getListForSaas([
            'merchant_name' => $merchant_name,
            'merchant_id' => $merchant_id,
            'oper_name' => $oper_name,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $status,
            'settlementCycleType' => $settlementCycleType,
            'isAutoSettlement' => $isAutoSettlement,
            'merchantType'   => $merchantType,
            'settlement_no'=>$settlementNo,
            'manual_gen'=>$manualGen,
        ]);
        $endTime = microtime(true);

        Log::debug('耗时: ', ['start time' => $startTime, 'end time' => $endTime, '耗时: ' => $endTime - $startTime]);

        return Result::success([
            'list' => $data->items(),
            'total' => $data->total(),
        ]);
    }

    /**
     * 下载Excel
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadExcel()
    {
        $merchant_name = request('merchant_name');
        $oper_name = request('oper_name');
        $merchant_id = request('merchant_id');
        $startDate = request('startDate');
        $endDate = request('endDate');
        $status = request('status');
        $settlementCycleType = request('settlement_cycle_type');
        $isAutoSettlement = request('is_auto_settlement');
        $settlementNo = request('settlement_no');
        $manualGen = request('manual_gen','false') == 'false' ? false : true;


        $uri = request()->getRequestUri();
        $merchantType = (strpos($uri,'csDownload')) ? SettlementPlatform::MERCHANT_TYPE_CS : ((request('merchant_type')) ? request('merchant_type') : SettlementPlatform::MERCHANT_TYPE_NORMAL);

        $query = SettlementPlatformService::getListForSaas([
            'merchant_name' => $merchant_name,
            'merchant_id' => $merchant_id,
            'oper_name' => $oper_name,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $status,
            'settlementCycleType' => $settlementCycleType,
            'isAutoSettlement' => $isAutoSettlement,
            'merchantType'   => $merchantType,
            'settlement_no' => $settlementNo,
            'manual_gen'=>$manualGen
        ],true);

        return (new SettlementPlatformExport($query))->download('结算报表.xlsx');
    }

    public function modifyStatus()
    {
        $id = request()->get('id');
        $settlement = SettlementPlatformService::getByIdModifyStatus($id);
        return Result::success($settlement);
    }

    public function getStatus()
    {
        $id = request()->get('id');
        $status = SettlementPlatformService::getKuaiQianBatchesStatus($id);
        return Result::success(['status'=>$status]);

    }

    public function manualStatus()
    {
        $id = request()->get('id');
        SettlementPlatformService::manualStatus($id);
        return Result::success();

    }


    public function reBatchAgain()
    {
        $id = request()->get('id');
        $settlement = SettlementPlatformService::genBatchAgain($id);
        return Result::success($settlement);
    }

    public function getSettlementOrders()
    {
        $this->validate(request(), [
            'settlement_id' => 'required|integer|min:1'
        ]);
        $settlementId   = request()->get('settlement_id');
        $data = OrderService::getListByPlatformSettlementId($settlementId);
        $list = collect($data->items());
        OrderService::getTextName($list);

        return Result::success([
            'list' => $list,
            'total' => $data->total(),
        ]);
    }

    public function export()
    {
        $this->validate(request(), [
            'settlement_id' => 'required|integer|min:1'
        ]);
        $query = $this->getListQuery();
        $fileName = '结算报表';
        header('Content-Type: application/vnd.ms-execl');
        header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');

        $fp = fopen('php://output', 'a');
        $title = ['交易时间', '订单号', '历时', '订单类型', '商品名称', '订单金额（元）','利率','结算金额', '手机号码', '订单状态', '发货方式', '商户名称', '运营中心名称', '发货地址', '联系人', '联系方式','支付方式', '备注'];
        foreach ($title as $key => $value) {
            $title[$key] = iconv('UTF-8', 'GBK', $value);
        }
        fputcsv($fp, $title);

        $query->chunk(1000, function ($data) use ($fp) {
            foreach ($data as $key => $value) {
                $item = [];

                $item['pay_time'] = '`'.$value['pay_time'];
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

                if($value['type'] == Order::TYPE_DISHES ){
                    $goodItems = DishesItem::where('dishes_id', $value['dishes_id'])->get();
                }elseif ($value['type'] == Order::TYPE_SCAN_QRCODE_PAY){
                    $goodItems = '无';
                }elseif ($value['type'] == Order::TYPE_SUPERMARKET) {
                    $goodItems = CsOrderGood::where('order_id',$value['id'])->get();
                }
                $item['goods_name'] = ($value['type']==Order::TYPE_GROUP_BUY) ? $value['goods_name']:Order::getAllGoodsName($value['type'], $goodItems);

                $item['pay_price'] = $value['pay_price'];
                $item['settlement_rate'] = ($value['settlement_rate']>1) ?floatval((int)$value['settlement_rate']):$value['settlement_rate'];
                $item['settlement_real_amount'] = $value['settlement_real_amount'];
                $item['notify_mobile'] = $value['notify_mobile'];
                $item['status'] = Order::getStatusText($value['status']);
                $item['deliver_type'] = ($value['type']!=Order::TYPE_SUPERMARKET)?'':Order::getDeliverTypeText($value['deliver_type']);
                $item['merchant_name'] = ($value['merchant_type']==Order::MERCHANT_TYPE_NORMAL) ? $value['merchant']['name'] : $value['csMerchant']['name'];
                $item['oper_name'] = $value['oper']['name'];
                $expressAddress = json_decode($value['express_address']);
                $item['address'] = (isset($expressAddress->province) ? $expressAddress->province : '') . (isset($expressAddress->city) ? $expressAddress->city : '') . (isset($expressAddress->area) ? $expressAddress->area : '') . (isset($expressAddress->address) ? $expressAddress->address : '');
                $item['contacts'] = isset($expressAddress->contacts) ? $expressAddress->contacts : '';
                $item['contact_phone'] = isset($expressAddress->contact_phone) ? $expressAddress->contact_phone : '';
                $item['pay_type'] = Order::getPayTypeText($value['pay_type']);
                $item['remark'] = $value['remark'];

                foreach ($item as $k => $v) {
                    $item[$k] = mb_convert_encoding($v, 'GBK', 'UTF-8');
                }
                fputcsv($fp, $item);
            }
            ob_flush();
            flush();
        });

    }

    private function getListQuery()
    {
        $settlementId = request('settlement_id');
        $payTargetType = Order::PAY_TARGET_TYPE_PLATFORM;
        $query = Order::where('settlement_id', $settlementId)
            ->where('pay_target_type', $payTargetType)
            ->with('merchant:id,name')
            ->with('oper:id,name')
            ->orderBy('id', 'desc');
        return $query;
    }


    public function manualSearch() {
        $this->validate(request(), [
            'merchant_id' => 'required|integer|min:1'
        ]);

        $merchant_id = request('merchant_id');
        $merchant = '';

        if ($merchant_id>=1000000000) {

            $merchant_type = SettlementPlatform::MERCHANT_TYPE_CS;
            $merchant = CsMerchant::find($merchant_id);
        } else {
            $merchant_type = SettlementPlatform::MERCHANT_TYPE_NORMAL;
            $merchant = Merchant::find($merchant_id);
        }

        if (empty($merchant)) {
            throw new BaseResponseException('商户ID错误');
        }

        $oper_info = Oper::find($merchant->oper_id);

        if (empty($oper_info)) {
            throw new BaseResponseException('商户数据错误');
        }

        if (!in_array($oper_info->pay_to_platform,[Oper::PAY_TO_PLATFORM_WITHOUT_SPLITTING,Oper::PAY_TO_PLATFORM_WITH_SPLITTING])) {
            throw new BaseResponseException('运营中心必须是支付到平台的');
        }


        $list = collect();

        $query = Order::where('merchant_id', $merchant->id)
            ->where('settlement_status', Order::SETTLEMENT_STATUS_NO )
            ->where('pay_target_type', Order::PAY_TARGET_TYPE_PLATFORM)
            ->where('status', Order::STATUS_FINISHED )
            ->where('merchant_type',$merchant_type);
        // 统计所有需结算金额
        $sum = $query->sum('pay_price');

        //获得结算周期时间
        $start_date = $query->min('finish_time');

        $settlement_platform = (object) [];
        $settlement_platform->merchant_id = $merchant->id;
        $settlement_platform->merchant_name = $merchant->name;
        $settlement_platform->merchant_type = $merchant_type;
        $settlement_platform->settlement_cycle_type = $merchant->settlement_cycle_type;
        $settlement_platform->oper_name = $oper_info->name;
        $settlement_platform->amount = $sum;



        $list->push($settlement_platform);

        return Result::success(['list'=>$list]);
    }

    public function manualGen()
    {
        $this->validate(request(), [
            'merchant_id' => 'required|integer|min:1'
        ]);

        $merchant_id = request('merchant_id');
        $merchant = '';

        if ($merchant_id>=1000000000) {

            $merchant_type = SettlementPlatform::MERCHANT_TYPE_CS;
            $merchant = CsMerchant::find($merchant_id);
        } else {
            $merchant_type = SettlementPlatform::MERCHANT_TYPE_NORMAL;
            $merchant = Merchant::find($merchant_id);
        }

        if (empty($merchant)) {
            throw new BaseResponseException('商户ID错误');
        }

        $oper_info = Oper::find($merchant->oper_id);

        if (empty($oper_info)) {
            throw new BaseResponseException('商户数据错误');
        }

        if (!in_array($oper_info->pay_to_platform,[Oper::PAY_TO_PLATFORM_WITHOUT_SPLITTING,Oper::PAY_TO_PLATFORM_WITH_SPLITTING])) {
            throw new BaseResponseException('运营中心必须是支付到平台的');
        }


        $query = Order::where('merchant_id', $merchant->id)
            ->where('settlement_status', Order::SETTLEMENT_STATUS_NO )
            ->where('pay_target_type', Order::PAY_TARGET_TYPE_PLATFORM)
            ->where('status', Order::STATUS_FINISHED )
            ->where('merchant_type',$merchant_type);
        // 统计所有需结算金额
        $sum = $query->sum('pay_price');

        if (empty($sum)) {
            throw new \Exception('没有未结算的订单');
        }
        //获得结算周期时间
        $start_date = $query->min('finish_time');
        if (empty($start_date)) {
            throw new \Exception('结算单数据错误');
        }
        $end_date = date('Y-m-d');

// 生成结算单，方便之后结算订单中保存结算信息
        $settlementNum = SettlementPlatformService::genSettlementNo(10);
        if( !$settlementNum ) {
            throw new \Exception('结算单号生成失败');
        }

        $type = ($merchant->settlement_cycle_type == Merchant::SETTLE_DAILY_AUTO) ? SettlementPlatform::TYPE_AGENT:SettlementPlatform::TYPE_DEFAULT;


        // 开启事务
        DB::beginTransaction();
        try{
            $settlementPlatform = new SettlementPlatform();
            $settlementPlatform->oper_id = $merchant->oper_id;
            $settlementPlatform->merchant_id = $merchant->id;
            $settlementPlatform->start_date = $start_date;
            $settlementPlatform->end_date = $end_date;
            $settlementPlatform->type = $type;
            $settlementPlatform->merchant_type = $merchant_type;
            $settlementPlatform->settlement_cycle_type = $merchant->settlement_cycle_type;
            $settlementPlatform->settlement_no = $settlementNum;
            $settlementPlatform->settlement_rate = $merchant->settlement_rate;
            $settlementPlatform->bank_open_name = $merchant->bank_open_name;
            $settlementPlatform->bank_card_no = $merchant->bank_card_no;
            $settlementPlatform->bank_card_type = $merchant->bank_card_type;
            $settlementPlatform->sub_bank_name = $merchant->bank_name .'|' . $merchant->sub_bank_name;
            $settlementPlatform->bank_open_address = $merchant->bank_province . ',' . $merchant->bank_city . ',' . $merchant->bank_area .'|' .$merchant->bank_open_address;
            $settlementPlatform->invoice_title = $merchant->invoice_title;
            $settlementPlatform->invoice_no = $merchant->invoice_no;
            $settlementPlatform->amount = 0;
            $settlementPlatform->charge_amount = 0;
            $settlementPlatform->real_amount = 0;
            $settlementPlatform->gen_type = SettlementPlatform::GEN_TYPE_MANUAL;
            $settlementPlatform->save();

            // 统计订单总金额与改变每笔订单状态
            $list = $query->select('id', 'settlement_charge_amount', 'settlement_real_amount', 'settlement_status', 'settlement_id', 'pay_price','settlement_rate')->get();
            $list->each( function(Order $item ) use ( $merchant, $settlementPlatform ){
                $item->settlement_charge_amount = $item->pay_price * $item->settlement_rate / 100;  // 手续费
                $item->settlement_real_amount = $item->pay_price - $item->settlement_charge_amount;   // 货款
                $item->settlement_status = Order::SETTLEMENT_STATUS_FINISHED;
                $item->settlement_id = $settlementPlatform->id;
                $item->save();

                // 结算实收金额
                $settlementPlatform->amount += $item->pay_price;
                $settlementPlatform->charge_amount += $item->settlement_charge_amount;
                $settlementPlatform->real_amount += $item->settlement_real_amount;
                $settlementPlatform->save();
            });
            DB::commit();
            return Result::success(['list'=>$settlementPlatform]);
        }catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


}
