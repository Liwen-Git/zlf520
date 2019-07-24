<?php
/**
 *
 */

namespace App\Exports;

use App\DataCacheService;
use App\Modules\Cs\CsGood;
use App\Modules\Cs\CsMerchant;
use App\Modules\Cs\CsPlatformCategoryService;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdminCsMerchantHotExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $query;
    protected $all_cats =[];

    public function __construct($query)
    {
        $this->query = $query;
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
            '商户ID',
            '商户名称',
            '城市',
            '运营中心名称',
            '活动状态',
            '活动商品数',
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
            $row->name,
            $row->province . ' ' . $row->city,
            $row->oper->name,
            CsMerchant::hotStatusName($row->hot_status),
            '`'.$row->hot_goods_count,

        ];
    }
}