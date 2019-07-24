<?php
/**
 * Created by PhpStorm.
 * User: tim.tang
 * Date: 2018/11/21/021
 * Time: 17:34
 */
namespace App\Http\Controllers\Admin;

use App\DataCacheService;
use App\Exceptions\BaseResponseException;
use App\Exports\CsGoodsExport;
use App\Exports\CsGoodsHotExport;
use App\Http\Controllers\Controller;
use App\Modules\Cs\CsGood;
use App\Modules\Cs\CsGoodService;
use App\Modules\Cs\CsMerchantCategory;
use App\Modules\Cs\CsMerchantCategoryService;
use App\Modules\Cs\CsMerchantService;
use App\Modules\Cs\CsPlatformCategoryService;
use App\Modules\Goods\Goods;
use App\Modules\Oper\OperService;
use App\Result;

class CsActivityGoodsController extends Controller
{

    /**
     * 获取列表 (分页)
     */
    public function getList()
    {
        $params = [];
        $cs_merchant_name = request('merchant_name','');
        if (!empty($cs_merchant_name)) {
            $params['cs_merchant_ids'] = CsMerchantService::getIdsByName($cs_merchant_name);
        }
        $oper_name = request('oper_name','');
        if (!empty($oper_name)) {
            $params['oper_ids'] = OperService::getIdsByName($oper_name);
        }
        $params['goods_name'] = request('goods_name','');
        $params['cs_platform_cat_id_level1'] = request('cs_platform_cat_id_level1','');
        $params['cs_platform_cat_id_level2'] = request('cs_platform_cat_id_level2','');
        $params['id'] = request('id',0);
        $params['status'] = request('status',[]);
        $params['audit_status'] = request('auditStatus',[]);
        $params['with_merchant'] = 1;
        $params['with_oper'] = 1;
        $params['hot_add_time'] = 1;
        $params['hot_status'] = request('hot_status',[]);;
        $params['cs_merchant_id'] = request('cs_merchant_id',0);
        if (!empty($params['cs_merchant_id'])) {
            $params['sort'] = 'hot_sort';
            $params['order'] = 'desc';
        } else {
            $params['sort'] = 3;
        }

        $data = CsGoodService::getList($params);

        return Result::success([
            'list' => $data->items(),
            'total' => $data->total(),
        ]);
    }

    /**
     * 导出
     */
    public function download()
    {
        $params = [];
        $cs_merchant_name = request('merchant_name','');
        if (!empty($cs_merchant_name)) {
            $params['cs_merchant_ids'] = CsMerchantService::getIdsByName($cs_merchant_name);
        }
        $oper_name = request('oper_name','');
        if (!empty($oper_name)) {
            $params['oper_ids'] = OperService::getIdsByName($oper_name);
        }
        $params['goods_name'] = request('goods_name','');
        $params['cs_platform_cat_id_level1'] = request('cs_platform_cat_id_level1','');
        $params['cs_platform_cat_id_level2'] = request('cs_platform_cat_id_level2','');
        $params['id'] = request('id',0);
        $params['status'] = request('status',[]);
        $params['audit_status'] = request('auditStatus',[]);
        $params['with_merchant'] = 1;
        $params['with_oper'] = 1;
        $params['hot_add_time'] = 1;
        $params['hot_status'] = request('hot_status',[]);
        $params['cs_merchant_id'] = request('cs_merchant_id',0);
        if (!empty($params['cs_merchant_id'])) {
            $params['sort'] = 'hot_sort';
            $params['order'] = 'desc';
        } else {
            $params['sort'] = 3;
        }

        if ($params['hot_status'] !== []) {
            $params['hot_status'] = explode(',', $params['hot_status']);
        }

        $query = CsGoodService::getList($params,true);
        return (new CsGoodsHotExport($query))->download('年货节商品列表.xlsx');

    }

    public function searchGoods() {
        $cs_merchant_keywords = request('cs_merchant_keywords');
        $cs_goods_keywords = request('cs_goods_keywords');
        $cs_goods_keywords = trim($cs_goods_keywords);
        if (empty($cs_goods_keywords)) {
            throw new BaseResponseException('请输入正确的商品信息');
        }
        $cs_merchant_id = $cs_merchant_keywords;
        $rt = CsGoodService::searchGoods($cs_merchant_id,$cs_goods_keywords);

        $data = [];
        if ($rt) {
            foreach ($rt as $k=>$v) {
                $d = [];
                $d['label'] = $v->goods_name . ' (' .CsGood::auditStatusName($v->audit_status) .' ' . CsGood::statusName($v->status) .  ')';
                $d['value'] = $v->id;
                if ($v->status != CsGood::STATUS_ON || $v->audit_status != CsGood::AUDIT_STATUS_SUCCESS) {
                    $d['disabled'] = true;
                }
                $data[] = $d;
            }
        }

        return Result::success($data);
    }

    /**
     * 添加热门商品
     */
    public function addHotGoods()
    {
        $cs_merchant_id = request('cs_merchant_id');
        $cs_goods_id = request('cs_goods_id');

        $cs_goods_id = trim($cs_goods_id);
        if (empty($cs_goods_id)) {
            throw new BaseResponseException('请输入正确的商品信息');
        }

        $rt = CsGoodService::addHotGoods($cs_merchant_id, $cs_goods_id);
        if ($rt) {
            return Result::success('添加成功');
        } else {
            return Result::error('添加失败');
        }
    }

    public function changeHotStatus()
    {

        $cs_goods_id = request('id');

        $goods = CsGood::findOrFail($cs_goods_id);
        if (empty($goods->hot_add_time)) {
            throw new BaseResponseException('不是年货节商品');
        }

        if ($goods->hot_status == CsGood::HOT_STATUS_OFF) {
            //上架操作

            if ($goods->audit_status != CsGood::AUDIT_STATUS_SUCCESS ) {
                throw new BaseResponseException('商户商品未审核通过');
            }
            if ($goods->status != CsGood::STATUS_ON) {
                throw new BaseResponseException('商户商品店内不是上架状态');
            }
            $goods->hot_status = CsGood::HOT_STATUS_ON;
            $goods->save();
        } else {

            $goods->hot_status = CsGood::HOT_STATUS_OFF;
            $goods->save();
        }
        $data['hot_status'] = $goods->hot_status;
        return Result::success('操作成功',$data);
    }

    public function changeTotalSort()
    {
        $this->validate(request(), [
            'id' => 'required|integer|min:1',
            'sort' => 'required|integer|min:0',

        ]);
        $id = request('id');
        $goods = CsGood::findOrFail($id);
        $goods->hot_total_sort = request('sort',0);
        $goods->save();
        return Result::success($goods);
    }


    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function changeSort()
    {
        $this->validate(request(), [
            'id' => 'required|integer|min:1',
            'type' => 'required',
        ]);
        $type = request('type');
        $cs_goods_id = request('id');

        CsGoodService::changeSort($cs_goods_id, $type);

        return Result::success();
    }

    /**
     * 获取子分类
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubCat()
    {

        $parent_id = request('parent_id',0);
        $rt = CsPlatformCategoryService::getSubCat($parent_id);

        $data = [['label'=>'全部','value'=>'0']];
        if ($rt) {
            foreach ($rt as $k=>$v) {
                $d['label'] = $v;
                $d['value'] = $k;
                $data[] = $d;
            }
        }

        return Result::success($data);

    }

    /**
     * 获取商品详情
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $this->validate(request(), [
            'id' => 'required|integer|min:1'
        ]);
        $id = request('id');
        $goods = CsGood::findOrFail($id);

        $goods->detail_imgs = $goods->detail_imgs ? explode(',', $goods->detail_imgs) : [];
        $goods->certificate1 = $goods->certificate1 ? explode(',', $goods->certificate1) : [];
        $goods->certificate2 = $goods->certificate2 ? explode(',', $goods->certificate2) : [];
        $goods->certificate3 = $goods->certificate3 ? explode(',', $goods->certificate3) : [];

        $all_cats = DataCacheService::getPlatformCats();
        $goods->cs_platform_cat_id_level1_name = $all_cats[$goods->cs_platform_cat_id_level1];
        $goods->cs_platform_cat_id_level2_name = $all_cats[$goods->cs_platform_cat_id_level2];
        $goods->status_name = CsGood::statusName($goods->status);
        $goods->audit_status_name = CsGood::auditStatusName($goods->audit_status);
        $goods->cs_merchant_name = CsMerchantService::getNameById($goods->cs_merchant_id);
        $goods->oper_name = OperService::getNameById($goods->oper_id);

        return Result::success($goods);
    }

    /**
     * 审核
     * @return \Illuminate\Http\JsonResponse
     */
    public function audit()
    {
        $this->validate(request(), [
            'id' => 'required|integer|min:1'
        ]);
        $id = request('id');
        $type = request('type');
        $oper_id = request()->get('current_user')->oper_id;
        $cs_goods = CsGood::findOrFail($id);

        if ($cs_goods->oper_id != $oper_id) {
            throw new BaseResponseException('参数错误');
        }

        if ($type == 1) {
            //审核通过自动上架
            $cs_cat = CsMerchantCategoryService::getMerchantCat($cs_goods->cs_merchant_id,$cs_goods->cs_platform_cat_id_level2);
            $cs_goods->status = $cs_cat->status == CsMerchantCategory::STATUS_ON?CsGood::STATUS_ON:CsGood::STATUS_OFF;
            $cs_goods->audit_status = CsGood::AUDIT_STATUS_SUCCESS;
        } else {
            //审核不通过自动下架
            $cs_goods->status = CsGood::STATUS_OFF;
            $cs_goods->audit_status = CsGood::AUDIT_STATUS_FAIL;
            $cs_goods->audit_suggestion = request('audit_suggestion','');
        }
        $rs = $cs_goods->save();
        return Result::success('审核成功');


    }
}
