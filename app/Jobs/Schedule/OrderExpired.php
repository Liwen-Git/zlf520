<?php

namespace App\Jobs\Schedule;

use App\Modules\CsOrder\CsOrderGoodService;
use App\Modules\Order\Order;
use App\Modules\Order\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class OrderExpired implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        Log::info('开始执行订单超时自动关闭定时任务');

        //超市订单取消自动退库存
        Order::where('status', 1)
            ->where('created_at', '<', Carbon::now()->subDay())
            ->chunk(1000, function(Collection $list){
                foreach ($list as $l) {
                    OrderService::orderCancel($l);
                }
            });

        Order::where('status', 1)
            ->where('created_at', '<', Carbon::now()->subDay())
            ->each(function($order){
                $order->status = Order::STATUS_CLOSED;
                $order->save();
            });


        Log::info('订单超时自动关闭定时任务执行完成');
    }
}
