<?php

namespace App\Console\Commands\Updates;

use App\Jobs\ConsumeQuotaUnfreezeJob;
use App\Jobs\FeeSplittingUnfreezeJob;
use App\Jobs\OrderFinishedJob;
use App\Modules\FeeSplitting\FeeSplittingRecord;
use App\Modules\Order\Order;
use App\Modules\Order\OrderService;
use App\Modules\Wallet\WalletConsumeQuotaRecord;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class V1_5_2_2 extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'update:v1.5.2.2';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        Log::info('未分润订单执行分润');

        $list1 = DB::select('select DISTINCT(order_no) from fee_splitting_records a where a.`status`=1');
        foreach ($list1 as $l) {
            $order = Order::where('order_no',$l->order_no)->firstOrFail();
            FeeSplittingUnfreezeJob::dispatch($order);
        }

        $list2 = DB::select('select DISTINCT(order_no) from wallet_consume_quota_records a where a.`status`=1');
        foreach ($list2 as $l) {
            $order = Order::where('order_no',$l->order_no)->firstOrFail();
            ConsumeQuotaUnfreezeJob::dispatch($order);
        }


        Log::info('未分润订单执行分润执行完成');

    }
}