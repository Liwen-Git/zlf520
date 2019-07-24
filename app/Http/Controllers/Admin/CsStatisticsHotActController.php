<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Modules\CsStatistics\CsStatisticsHotActService;
use App\Result;
use Illuminate\Support\Carbon;

class CsStatisticsHotActController extends Controller
{
    private static function getStaGoodsQuery($withQuery = false)
    {
        $startDate = request('startDate');
        $endDate = request('endDate');

        if($startDate && $startDate instanceof Carbon){
            $startDate = $startDate->format('Y-m-d');
        }
        if($endDate && $endDate instanceof Carbon){
            $endDate = $endDate->format('Y-m-d');
        }

        $goodsName = request('goodsName');
        $goodsType = request('goodsType');
        $merchantNameOrId = request('merchantNameOrId');
        $operNameOrId = request('operNameOrId');
        $orderColumn = request('orderColumn');
        $orderType = request('orderType');
        $pageSize = request('pageSize', 15);

        $params = compact('startDate', 'endDate', 'goodsName', 'goodsType', 'merchantNameOrId', 'operNameOrId', 'orderColumn', 'orderType', 'pageSize');
        $query = CsStatisticsHotActService::getStaGoodsList($params, $withQuery);
        return $query;
    }

    /**
     * 年货节活动 商品统计列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStaGoodsList()
    {
        $query = self::getStaGoodsQuery(true);

        $saleNumSum = $query->sum('sale_num');
        $saleTotalSum = $query->sum('sale_total');
        $data = $query->paginate();
        return Result::success([
            'list' => $data->items(),
            'total' => $data->total(),
            'sale_num_sum' => $saleNumSum,
            'sale_total_sum' => number_format($saleTotalSum, 2, '.', ''),
        ]);
    }

    /**
     * 年货节活动 商品导出
     */
    public function exportStaGoods()
    {
        $query = self::getStaGoodsQuery(true);

        $fileName = '年货节活动商品统计';
        header('Content-Type: application/vnd.ms-execl');
        header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');

        $fp = fopen('php://output', 'a');
        $title = ['时间', '商品名', '商品类型', '商户', '运营中心', '销售价', '市场价', '活动销量', '活动销售额'];
        foreach ($title as $key => $value) {
            $title[$key] = iconv('UTF-8', 'GBK', $value);
        }
        fputcsv($fp, $title);

        $query->chunk(1000, function ($data) use ($fp) {
            foreach ($data as $key => $value) {
                $item = [];

                $item['date'] = $value['date'];
                $item['goods_name'] = $value['csGoods']['goods_name'];
                $item['goods_type'] = ['', '团购', '', '单品', '超市'][$value['goods_type']];
                $item['merchant'] = "[{$value['csMerchant']['id']}]{$value['csMerchant']['name']}";
                $item['oper'] = "[{$value['oper']['id']}]{$value['oper']['name']}";
                $item['pay_price'] = $value['pay_price'];
                $item['market_price'] = $value['market_price'];
                $item['sale_num'] = $value['sale_num'];
                $item['sale_total'] = $value['sale_total'];

                foreach ($item as $k => $v) {
                    $item[$k] = mb_convert_encoding($v, 'GBK', 'UTF-8');
                }
                fputcsv($fp, $item);
            }
            ob_flush();
            flush();
        });
    }


    private static function getStaMerchantQuery($withQuery = false)
    {
        $startDate = request('startDate');
        $endDate = request('endDate');

        if($startDate && $startDate instanceof Carbon){
            $startDate = $startDate->format('Y-m-d');
        }
        if($endDate && $endDate instanceof Carbon){
            $endDate = $endDate->format('Y-m-d');
        }

        $merchantNameOrId = request('merchantNameOrId');
        $operNameOrId = request('operNameOrId');
        $orderColumn = request('orderColumn');
        $orderType = request('orderType');
        $pageSize = request('pageSize', 15);

        $params = compact('startDate', 'endDate', 'merchantNameOrId', 'operNameOrId', 'orderColumn', 'orderType', 'pageSize');
        $query = CsStatisticsHotActService::getStaMerchantList($params, $withQuery);
        return $query;
    }

    /**
     * 年货节活动 商户统计列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStaMerchantList()
    {
        $query = self::getStaMerchantQuery(true);

        $saleNumSum = $query->sum('sale_num');
        $saleTotalSum = $query->sum('sale_total');
        $data = $query->paginate();
        return Result::success([
            'list' => $data->items(),
            'total' => $data->total(),
            'sale_num_sum' => $saleNumSum,
            'sale_total_sum' => number_format($saleTotalSum, 2, '.', ''),
        ]);
    }

    /**
     * 年货节活动 商户统计列表 导出
     */
    public function exportStaMerchant()
    {
        $query = self::getStaMerchantQuery(true);

        $fileName = '年货节活动商户统计';
        header('Content-Type: application/vnd.ms-execl');
        header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');

        $fp = fopen('php://output', 'a');
        $title = ['时间', '商户', '运营中心', '活动商品数', '活动销量', '活动销售额'];
        foreach ($title as $key => $value) {
            $title[$key] = iconv('UTF-8', 'GBK', $value);
        }
        fputcsv($fp, $title);

        $query->chunk(1000, function ($data) use ($fp) {
            foreach ($data as $key => $value) {
                $item = [];

                $item['date'] = $value['date'];
                $item['merchant'] = "[{$value['csMerchant']['id']}]{$value['csMerchant']['name']}";
                $item['oper'] = "[{$value['oper']['id']}]{$value['oper']['name']}";
                $item['goods_num'] = $value['goods_num'];
                $item['sale_num'] = $value['sale_num'];
                $item['sale_total'] = $value['sale_total'];

                foreach ($item as $k => $v) {
                    $item[$k] = mb_convert_encoding($v, 'GBK', 'UTF-8');
                }
                fputcsv($fp, $item);
            }
            ob_flush();
            flush();
        });
    }

    private static function getStaOperQuery($withQuery = false)
    {
        $startDate = request('startDate');
        $endDate = request('endDate');

        if($startDate && $startDate instanceof Carbon){
            $startDate = $startDate->format('Y-m-d');
        }
        if($endDate && $endDate instanceof Carbon){
            $endDate = $endDate->format('Y-m-d');
        }

        $operNameOrId = request('operNameOrId');
        $orderColumn = request('orderColumn');
        $orderType = request('orderType');
        $pageSize = request('pageSize', 15);

        $params = compact('startDate', 'endDate', 'operNameOrId', 'orderColumn', 'orderType', 'pageSize');
        $query = CsStatisticsHotActService::getStaOperList($params, $withQuery);
        return $query;
    }

    /**
     * 年货节活动 运营中心统计列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStaOperList()
    {
        $query = self::getStaOperQuery(true);

        $saleNumSum = $query->sum('sale_num');
        $saleTotalSum = $query->sum('sale_total');
        $data = $query->paginate();
        return Result::success([
            'list' => $data->items(),
            'total' => $data->total(),
            'sale_num_sum' => $saleNumSum,
            'sale_total_sum' => number_format($saleTotalSum, 2, '.', ''),
        ]);
    }

    /**
     * 年货节活动 运营中心统计列表 导出
     */
    public function exportStaOper()
    {
        $query = self::getStaOperQuery(true);

        $fileName = '年货节活动运营中心统计';
        header('Content-Type: application/vnd.ms-execl');
        header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');

        $fp = fopen('php://output', 'a');
        $title = ['时间', '运营中心', '活动商品数', '活动销量', '活动销售额'];
        foreach ($title as $key => $value) {
            $title[$key] = iconv('UTF-8', 'GBK', $value);
        }
        fputcsv($fp, $title);

        $query->chunk(1000, function ($data) use ($fp) {
            foreach ($data as $key => $value) {
                $item = [];

                $item['date'] = $value['date'];
                $item['oper'] = "[{$value['oper']['id']}]{$value['oper']['name']}";
                $item['goods_num'] = $value['goods_num'];
                $item['sale_num'] = $value['sale_num'];
                $item['sale_total'] = $value['sale_total'];

                foreach ($item as $k => $v) {
                    $item[$k] = mb_convert_encoding($v, 'GBK', 'UTF-8');
                }
                fputcsv($fp, $item);
            }
            ob_flush();
            flush();
        });
    }
}
