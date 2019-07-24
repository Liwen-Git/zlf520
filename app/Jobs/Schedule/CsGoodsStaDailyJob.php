<?php

namespace App\Jobs\Schedule;

use App\Modules\Cs\CsGoodService;
use App\Modules\CsOrder\CsOrderGood;
use App\Modules\Order\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class CsGoodsStaDailyJob implements ShouldQueue
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
        $start_date = date('Y-m-d',time() - 30 * 86400);
        Log::info('超市商品最近30天销售数量统计'.$start_date);
        $status = [
            Order::STATUS_FINISHED,
            Order::STATUS_REFUNDED,
            Order::STATUS_PAID,
            Order::STATUS_UNDELIVERED,
            Order::STATUS_DELIVERED,
            Order::STATUS_NOT_TAKE_BY_SELF
        ];

        $csOrderGoodsList = CsOrderGood::whereHas('order', function (Builder $query) use ($start_date, $status) {
            $query->where('created_at', '>', $start_date)
                ->whereIn('status', $status);
        })
            ->select('cs_goods_id')
            ->groupBy('cs_goods_id')
            ->selectRaw('sum(number) as sale_count')
            ->get();
        if ($csOrderGoodsList->isNotEmpty()) {
            $csOrderGoodsList->each(function ($item) {
                CsGoodService::updateSaleCount($item->cs_goods_id, $item->sale_count);
            });
        }
        Log::info('超市商品最近30天销售数量统计结束');
    }
}
