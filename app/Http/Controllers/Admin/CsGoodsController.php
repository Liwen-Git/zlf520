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
use App\Exports\CsGoodsOperExport;
use App\Http\Controllers\Controller;
use App\Modules\Cs\CsGood;
use App\Modules\Cs\CsGoodService;
use App\Modules\Cs\CsMerchantCategory;
use App\Modules\Cs\CsMerchantCategoryService;
use App\Modules\Cs\CsMerchantService;
use App\Modules\Cs\CsPlatformCategoryService;
use App\Modules\Oper\OperService;
use App\Result;

class CsGoodsController extends Controller
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
        $params['cs_merchant_id'] = request('cs_merchant_id',0);
        $params['sort'] = 2;
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
        $params['cs_merchant_id'] = request('cs_merchant_id',0);

        $query = CsGoodService::getList($params,true);
        return (new CsGoodsOperExport($query))->download('商品列表.xlsx');

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

        $goods = CsGoodService::adminDetail($id);
        if(empty($goods)){
            throw new DataNotFoundException('商品信息不存在或已删除');
        }

        $goods->detail_imgs = $goods->detail_imgs ? explode(',', $goods->detail_imgs) : [];
        $goods->certificate1 = $goods->certificate1 ? explode(',', $goods->certificate1) : [];
        $goods->certificate2 = $goods->certificate2 ? explode(',', $goods->certificate2) : [];
        $goods->certificate3 = $goods->certificate3 ? explode(',', $goods->certificate3) : [];

        $all_cats = DataCacheService::getPlatformCats();
        $goods->cs_platform_cat_id_level1_name = $all_cats[$goods->cs_platform_cat_id_level1];
        $goods->cs_platform_cat_id_level2_name = $all_cats[$goods->cs_platform_cat_id_level2];
        $goods->status_name = CsGood::statusName($goods->status);
        $goods->audit_status_name = CsGood::auditStatusName($goods->audit_status);

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
        $cs_goods = CsGood::findOrFail($id);



        if ($type == 1) {
            //审核通过自动上架
            $cs_cat = CsMerchantCategoryService::getMerchantCat($cs_goods->cs_merchant_id,$cs_goods->cs_platform_cat_id_level2);
            $cs_goods->status = $cs_cat->status == CsMerchantCategory::STATUS_ON?CsGood::STATUS_ON:CsGood::STATUS_OFF;
            $cs_goods->audit_status = CsGood::AUDIT_STATUS_SUCCESS;
            $cs_goods->saas_audit_status = 1;
        } else {
            //审核不通过自动下架
            $cs_goods->status = CsGood::STATUS_OFF;
            $cs_goods->audit_status = CsGood::AUDIT_STATUS_FAIL;
            $cs_goods->audit_suggestion = request('audit_suggestion','');
            $cs_goods->saas_audit_status = 2;
        }

        $rs = $cs_goods->save();
        return Result::success('审核成功');


    }


    public function auditAllNotPassed()
    {
        $ids = request('ids',[]);

        if(empty($ids)){
            throw new DataNotFoundException('请先选择要操作的商品');
        }

        CsGood::whereIn('id',$ids)
            ->update([
                'status' => CsGood::STATUS_OFF,
                'audit_status'=>CsGood::AUDIT_STATUS_FAIL,
                'saas_audit_status'=>2,
                ]);
        return Result::success('审核成功');
    }

}
