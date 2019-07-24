<?php

namespace App\Jobs\Cs;

use App\Modules\CsStatistics\CsStatisticsHotActService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;

class HotActStatisticsDaily implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $endTime;

    /**
     * Create a new job instance.
     *
     * @param $endTime
     */
    public function __construct($endTime)
    {
        if ($endTime instanceof Carbon) {
            $this->endTime = $endTime->format('Y-m-d 23:59:59');
        } else {
            $this->endTime = date('Y-m-d 23:59:59', strtotime($endTime));
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        CsStatisticsHotActService::staGoods($this->endTime);
        CsStatisticsHotActService::staMerchant($this->endTime);
        CsStatisticsHotActService::staOper($this->endTime);
    }
}
