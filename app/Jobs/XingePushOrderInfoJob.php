<?php

namespace App\Jobs;

use App\Modules\Order\Order;
use App\Modules\User\UserDevice;
use App\Modules\User\UserDeviceService;
use App\Modules\Xinge\XingeService;
use App\Support\Xinge\Xinge;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class XingePushOrderInfoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = $this->order;
        if ($order->type == Order::TYPE_SCAN_QRCODE_PAY || $order->type == Order::TYPE_SCAN_QRCODE_PAY_WITH_PRICE) {
            $app = Xinge::MERCHANT_APP;
            $account = config('xinge.merchant_app_prefix').$order->merchant_id;
            $title = '大千生活';
            $content = "已到账{$order->pay_price}元，请注意查收";
            $subtitle = '';
            $custom = ['order_id' => $order->id, 'type' => $order->type, 'pay_price' => $order->pay_price];
        } else {
            $app = Xinge::MERCHANT_APP;
            $account = config('xinge.merchant_app_prefix').$order->merchant_id;
            $title = '大千生活';
            $content = "您有一笔新的订单啦，立即查看";
            $subtitle = '';
            $custom = ['order_id' => $order->id, 'type' => $order->type, 'pay_price' => $order->pay_price];
        }
        $userDevice = UserDeviceService::getUserDevice($order->merchant_id, UserDevice::USER_TYPE_MERCHANT);
        if (!empty($userDevice) && $userDevice->device_type && $userDevice->device_no) {
            XingeService::pushByAccount($app, $userDevice->device_type, $account, $title, $content, $subtitle, $custom);
        }
    }
}
