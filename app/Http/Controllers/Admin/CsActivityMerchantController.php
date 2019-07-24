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
use App\Exports\AdminCsMerchantHotExport;
use App\Exports\CsGoodsExport;
use App\Exports\CsGoodsHotExport;
use App\Http\Controllers\Controller;
use App\Modules\Cs\CsGood;
use App\Modules\Cs\CsGoodService;
use App\Modules\Cs\CsMerchant;
use App\Modules\Cs\CsMerchantCategory;
use App\Modules\Cs\CsMerchantCategoryService;
use App\Modules\Cs\CsMerchantService;
use App\Modules\Cs\CsPlatformCategoryService;
use App\Modules\Goods\Goods;
use App\Modules\Oper\OperService;
use App\Result;
use test\Mockery\Fixtures\EmptyTestCaseV5;

class CsActivityMerchantController extends Controller
{

    /**
     * 获取列表 (分页)
     */
    public function getList()
    {
        $params = [];
        $params['merchant_name'] = request('merchant_name','');
        $params['oper_name'] = request('oper_name','');
        if (!empty($params['oper_name'])) {
            $params['oper_ids'] = OperService::getIdsByName($params['oper_name']);
        }
        $params['id'] = request('id',0);
        $params['oper_id'] = request('oper_id',[]);
        $params['with_merchant'] = 1;
        $params['with_oper'] = 1;
        $params['hot_add_time'] = 1;
        $params['hot_status'] = request('hot_status',[]);;
        $params['cs_merchant_id'] = request('cs_merchant_id',0);

        $params['sort'] = 'hot_add_time';
        $params['order'] = 'desc';


        $data = CsMerchantService::getListHot($params);

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
        $params['merchant_name'] = request('merchant_name','');
        $params['oper_name'] = request('oper_name','');
        if (!empty($params['oper_name'])) {
            $params['oper_ids'] = OperService::getIdsByName($params['oper_name']);
        }
        $params['id'] = request('id',0);
        $params['oper_id'] = request('oper_id',[]);
        $params['with_merchant'] = 1;
        $params['with_oper'] = 1;
        $params['hot_add_time'] = 1;
        $params['hot_status'] = request('hot_status',[]);;
        $params['cs_merchant_id'] = request('cs_merchant_id',0);
        $params['sort'] = 'hot_add_time';
        $params['order'] = 'desc';

        if ($params['hot_status'] !== []) {
            $params['hot_status'] = explode(',', $params['hot_status']);
        }
        $query = CsMerchantService::getListHot($params,true);

        return (new AdminCsMerchantHotExport($query))->download('年货节活动商户列表.xlsx');

    }

    public function searchMerchant() {
        $cs_merchant_keywords = request('cs_merchant_keywords');
        $cs_merchant_keywords = trim($cs_merchant_keywords);
        if (empty($cs_merchant_keywords)) {
            throw new BaseResponseException('请输入正确的商户信息');
        }
        $rt = CsMerchantService::search($cs_merchant_keywords);


        $data = [];
        if ($rt) {
            foreach ($rt as $k=>$v) {
                $d = [];
                $d['label'] = $v->name . ' (' .CsMerchant::auditStatusName($v->audit_status) .' ' . CsMerchant::statusName($v->status) .  ')';
                $d['value'] = $v->id;

                if ($v->status != CsMerchant::STATUS_ON || $v->audit_status != CsMerchant::AUDIT_STATUS_SUCCESS) {
                    $d['disabled'] = true;
                }

                if (!empty($v->hot_add_time)) {
                    $d['label'] = $v->name . ' ( 已添加)';
                    $d['disabled'] = true;
                }
                $data[] = $d;
            }
        }

        return Result::success($data);
    }

    /**
     * 添加热门商户
     */
    public function addHotMerchants()
    {
        $cs_merchant_id = request('cs_merchant_id');
        $cs_merchant_id = trim($cs_merchant_id);
        if (empty($cs_merchant_id)) {
            throw new BaseResponseException('请输入正确的商户信息');
        }
        $cs_merchant = CsMerchant::findOrFail($cs_merchant_id);

        if ($cs_merchant->status != CsMerchant::STATUS_ON) {
            throw new BaseResponseException('商户未启用');
        }

        if ($cs_merchant->audit_status != CsMerchant::AUDIT_STATUS_SUCCESS) {
            throw new BaseResponseException('商户未审核通过');
        }

        if (!empty($cs_merchant->hot_add_time)) {
            throw new BaseResponseException('商户已经是年货节活动商户');
        }

        $cs_merchant->hot_add_time = date('Y-m-d H:i:s');
        $cs_merchant->hot_status = CsMerchant::HOT_STATUS_OFF;
        $rs = $cs_merchant->save();
        if ($rs) {
            return Result::success('添加成功');
        } else {
            return Result::error('添加失败');
        }
    }

    public function changeHotStatus()
    {

        $cs_merchant_id = request('id');

        $cs_merchant = CsMerchant::findOrFail($cs_merchant_id);
        if (empty($cs_merchant->hot_add_time)) {
            throw new BaseResponseException('不是年货节活动商户');
        }

        if ($cs_merchant->hot_status == CsMerchant::HOT_STATUS_OFF) {
            //上架操作

            if ($cs_merchant->audit_status != CsMerchant::AUDIT_STATUS_SUCCESS ) {
                throw new BaseResponseException('商户未审核通过');
            }
            if ($cs_merchant->status != CsMerchant::STATUS_ON) {
                throw new BaseResponseException('商户未启用');
            }

            $cnt = CsGoodService::getHotCount($cs_merchant_id);
            if (empty($cnt)) {
                throw new BaseResponseException('请先上架年货节商品');
            }

            $cs_merchant->hot_status = CsMerchant::HOT_STATUS_ON;
            $cs_merchant->save();
        } else {

            //商户不参加活动，商品也从活动中下架
            CsGood::where('cs_merchant_id',$cs_merchant_id)->update(['hot_status'=>CsGood::HOT_STATUS_OFF]);

            $cs_merchant->hot_status = CsMerchant::HOT_STATUS_OFF;
            $cs_merchant->save();
        }
        $data['hot_status'] = $cs_merchant->hot_status;
        return Result::success('操作成功',$data);
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
