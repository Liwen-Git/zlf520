<?php

namespace App\Console\Commands;

use App\Modules\Settlement\SettlementPlatformService;
use Illuminate\Console\Command;


class GenBatchByDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen_batch_by_dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        //$dates = ['2018-11-30'];
        $dates = ['2018-12-20','2018-12-21','2018-12-22','2018-12-23','2018-12-24'];
        SettlementPlatformService::genBatchByDates($dates);
        dd('ok');
    }
}
