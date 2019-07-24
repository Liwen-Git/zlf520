<?php
/**
 *
 */

namespace App\Exports;

use App\DataCacheService;
use App\Modules\Cs\CsGood;
use App\Modules\Cs\CsGoodService;
use App\Modules\Cs\CsPlatformCategoryService;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CsGoodsHotExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $query;
    protected $all_cats =[];

    public function __construct($query)
    {
        $this->query = $query;
        $this->all_cats = DataCacheService::getPlatformCats();
    }

    /**
     * @return Builder
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            '加入活动时间',
            '商品ID',
            '商品名称',
            '商户ID',
            '商户名称',
            '城市',
            '运营中心名称',
            '商品活动状态',
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->hot_add_time,
            $row->id,
            $row->goods_name,
            $row->cs_merchant_id,
            $row->cs_merchant->name,
            $row->cs_merchant->province . ' ' . $row->cs_merchant->city,
            $row->oper_name,
            CsGood::hotStatusName($row->hot_status)
        ];
    }
}