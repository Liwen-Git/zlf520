<?php

namespace App\Console\Commands;

use App\DataCacheService;
use App\Modules\Cs\CsMerchantCategoryService;
use App\Modules\Settlement\SettlementPlatformKuaiQianBatchService;
use Illuminate\Console\Command;

class TestCsMerchantCatCache extends Command
{
    /**
     * example: php artisan test:settlementAgentPay 1,2,3
     *
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:cs_merchant_cache {cs_merchant_id}';

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
        $cs_merchant_id = $this->argument('cs_merchant_id');
        CsMerchantCategoryService::synPlatFormCat($cs_merchant_id);
        DataCacheService::delCsMerchantCats($cs_merchant_id);
        dd('ok');

    }
}
