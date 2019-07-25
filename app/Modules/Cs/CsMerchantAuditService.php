<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/20
 * Time: 11:29 AM
 */
namespace App\Modules\Cs;

use App\BaseService;
use App\Exceptions\BaseResponseException;
use App\Exceptions\ParamInvalidException;
use App\Modules\Merchant\Merchant;
use App\Modules\Merchant\MerchantAudit;
use App\Modules\Oper\Oper;
use App\ResultCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class CsMerchantAuditService extends BaseService {

    /**
     * 获取审核结果列表
     * @param array $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getAuditResultList(array $params = [])
    {
        $operId = array_get($params, 'operId');
        $merchantId = array_get($params, 'merchantId');
        $status = array_get($params, 'status');
        $name = array_get($params, 'name');

        $query = CsMerchantAudit::query();
        if($operId){
            $query->where('oper_id', $operId);
        }
        if($merchantId){
            $query->where('cs_merchant_id', $merchantId);
        }
        if($status){
            if(is_array($status)){
                $query->whereIn('status', $status);
            }else {
                $query->where('status', $status);
            }
        }
        if($name){
            $query->where('name', 'like', "%$name%");
        }
        $data = $query->orderByDesc('updated_at')->paginate();
        $lastAuditList = [];            // 用以存儲已查询的值,减少
        $data->each(function($item) {
            $item->canModify = self::canModify($lastAuditList,$item);
            $item->operName = Oper::where('id', $item->oper_id)->value('name');
            $item->data_after = json_decode($item->data_after,true);
            if($item->cs_merchant_id){
                $item->cs_merchant_detail = CsMerchantService::getById($item->cs_merchant_id);
            }
        });
        return $data;
    }

    /**
     * 判断是否允许重新查询
     * @param $lastAuditList
     * @param $audit
     * @return string
     */
    public static function canModify(&$lastAuditList,$audit){
        if(!in_array($audit->status,[CsMerchantAudit::AUDIT_STATUS_RECALL,CsMerchantAudit::AUDIT_STATUS_FAIL])) return 'no';
        $column = ($audit->cs_merchant_id!=0) ? 'cs_merchant_id': 'name';
        // 如果已查询过，则用数组数据进行判断
        if(isset($lastAuditList[$column])) return ($lastAuditList[$column]==$audit->$column) ? 'yes' : 'no';
        $lastAuditList = CsMerchantAudit::where($column,$audit->$column)
            ->where('status',$audit->status)
            ->orderBy('id','desc')
            ->first();
        if(!$lastAuditList)return 'no';
        $tempArr = (is_array($lastAuditList[$column])) ? $lastAuditList[$column] : [];
        $tempArr[$audit->$column] = $lastAuditList->id;
        $lastAuditList[$column] = $tempArr;
        return ($lastAuditList->id!=$audit->id) ?'no' :'yes';
    }

    /**
     * 根据ID获取商户信息
     * @param $id
     * @param array|string $fields
     * @return CsMerchantAudit
     */
    public static function getById($id, $fields = ['*'])
    {
        if(is_string($fields)){
            $fields = explode(',', $fields);
        }
        return CsMerchantAudit::find($id, $fields);
    }

    /**
     * @param $merchantId
     * @return CsMerchant
     */
    public static function getNewestAuditRecordByMerchantId($merchantId)
    {
        $merchant = CsMerchant::where('id', $merchantId)
            ->select('id', 'name','audit_suggestion')
            ->first();

        return $merchant;
    }

    public static function addAudit($params)
    {
        $operId = ($params['oper_id'])??0;
        $type = ($params['type'])??CsMerchantAudit::INSERT_TYPE;
        $csMerchantId = ($params['csMerchantId'])??0;
        $name = ($params['name'])??'';
        $dataBefore = ($params['dataBefore'])??'';
        $dataAfter = ($params['dataAfter'])??'';
        $dataModify = ($params['dataModify'])??'';

        $audit = new CsMerchantAudit();
        $audit->oper_id = $operId;
        $audit->type = $type;
        $audit->cs_merchant_id = $csMerchantId;
        $audit->name = $name;
        $audit->data_before = $dataBefore;
        $audit->data_after = $dataAfter;
        $audit->data_modify = $dataModify;
        $audit->status = CsMerchantAudit::AUDIT_STATUS_AUDITING;
        $audit->save();
        return $audit;
    }

    public static function cancelAudit()
    {
        // todo 取消审核
    }

    /**
     * 根据商户ID获取待审核的审核记录
     * @param $merchantId
     * @param $operId
     * @return MerchantAudit
     */
    public static function getUnauditRecordByMerchantId($merchantId, $operId)
    {
        // 获取最后一条审核记录用到这方法
        $merchantCurrentAudit = MerchantAudit::where('merchant_id', $merchantId)
            ->where('oper_id', $operId)
            ->whereIn('status', [Merchant::AUDIT_STATUS_AUDITING, Merchant::AUDIT_STATUS_RESUBMIT])
            ->orderByDesc('updated_at')
            ->first();
        return $merchantCurrentAudit;
    }

    /**
     * 审核通过
     * @param $merchant CsMerchant|null 要审核的商户
     * @param $auditSuggestion string 审核意见
     * @param $merchantAudit CsMerchantAudit 审核记录
     * @return CsMerchant|null
     * @throws \Exception
     */
    public static function auditSuccess($merchant, $auditSuggestion, $merchantAudit)
    {
        if(is_null($merchant)){
            // 商户表无数据则新增
            $merchant = new CsMerchant();
            $merchant->settlement_cycle_type = CsMerchant::SETTLE_DAY_ADD_ONE;
        }

        self::checkName($merchant);

        $saveColumn = json_decode($merchantAudit->data_after);
        $csMerchantColumn = Schema::getColumnListing('cs_merchants');
        $unReplaceColumn = ['id','status'];
        foreach ($saveColumn as $k=>$v){
            // 如果为过滤字段或者字段不在数据表内，则退出
            if(in_array($k,$unReplaceColumn)||(!in_array($k,$csMerchantColumn))){
                continue;
            }
            $merchant->$k = $v;
            if (is_array($merchant->licence_pic_url)) {
                $merchant->licence_pic_url = implode(',', $merchant->licence_pic_url);
            }
        }

        $merchant->audit_status = CsMerchant::AUDIT_STATUS_SUCCESS;
        $merchant->status = CsMerchant::STATUS_ON;
        $merchant->audit_suggestion = $auditSuggestion ?? '';
        $merchant->oper_id = $merchantAudit->oper_id;
        $merchant->active_time = Carbon::now();
        if (!$merchant->first_active_time) {
            $merchant->first_active_time = Carbon::now();
        }

        // 修改审核记录状态
        $merchantAudit->suggestion = $auditSuggestion ??'';
        $merchantAudit->status = CsMerchantAudit::AUDIT_STATUS_SUCCESS;
        $merchantAudit->audit_time = date('Y-m-d H:i:s');

        // 开启事务
        DB::beginTransaction();
        try{
            $merchant->save();
            $merchantAudit->cs_merchant_id = $merchant->id;
            $merchantAudit->save();
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw new BaseResponseException( $e->getMessage(),ResultCode::DB_INSERT_FAIL);
        }

        return $merchant;

    }

    /**
     * 审核不通过
     * @param $merchant null|CsMerchant 要审核的商户
     * @param $auditSuggestion string 审核意见
     * @param $merchantAudit CsMerchantAudit
     * @return CsMerchant
     * @throws \Exception
     */
    public static function auditFail($merchant, $auditSuggestion, $merchantAudit)
    {
        $merchantAudit->status = CsMerchantAudit::AUDIT_STATUS_FAIL;
        $merchantAudit->suggestion = $auditSuggestion ?? '';
        $merchantAudit->audit_time = date('Y-m-d H:i:s');
        $merchantAudit->save();
        // 取消修改商户状态逻辑
        /*// 开启事务
        DB::beginTransaction();
        try{
            if(!is_null($merchant)){
                $merchant->audit_status = CsMerchant::AUDIT_STATUS_FAIL;
                $merchant->status   = CsMerchant::STATUS_OFF;
                $merchant->audit_suggestion = $auditSuggestion ?? '';
                $merchant->save();
            }
            $merchantAudit->save();
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw new BaseResponseException( $e->getMessage(),ResultCode::DB_INSERT_FAIL);
        }*/
        return $merchant;
    }

    public static function addDraft()
    {

    }

    public static function reSubmit()
    {

    }

    public static function checkName(CsMerchant $csMerchant)
    {
        // 判断是否存在重名
        $existMerchant = Merchant::where('name',$csMerchant->name)->exists();
        if($existMerchant){
            throw new BaseResponseException('该商户名重复，请修改');
        }
        $existCsMerchant = CsMerchant::where('name',$csMerchant->name)->where('id','!=',$csMerchant->id)->exists();
        if($existCsMerchant){
            throw new BaseResponseException('该商户名重复，请修改。');
        }

        $existMerchant = Merchant::where('signboard_name',$csMerchant->signboard_name)->exists();
        if($existMerchant){
            throw new BaseResponseException('该商户招牌名重复，请修改');
        }
        $existCsMerchant = CsMerchant::where('signboard_name',$csMerchant->signboard_name)->where('id','!=',$csMerchant->id)->exists();
        if($existCsMerchant){
            throw new BaseResponseException('该商户招牌名重复，请修改。');
        }
    }

    /**
     * @param $id
     * @param $operId
     * @param $dataType
     * @return CsMerchantAudit
     * @throws \Exception
     */
    public static function editOrAddMerchantAudit($id,$operId,$dataType){
        $merchantId = 0;
        $draftId = 0;
        $csMerchant = '';
        $csMerchantOri = '';//原来的商户信息

        if($dataType=='draft'){
            // 如果为草稿箱数据，则ID为草稿表ID
            if($id){
                $draftId = $id;
                $id = 0;
            }
            $csMerchant = new CsMerchant();
        } elseif ($dataType == 'audit') {
            if (!empty($id)) {
                $csMerchant = CsMerchant::find($id);
                $csMerchantOri = $csMerchant;
                $merchantId = $csMerchant->id;
            } else {
                $csMerchant = new CsMerchant();
            }

        } elseif ($dataType == 'csMerchant') {
            // 如果来源为超市商家编辑
            $csMerchant = CsMerchant::find($id);
            $csMerchantOri = $csMerchant;
            $merchantId = $csMerchant->id;
        } else {
            $csMerchant = new CsMerchant();
        }

        $csMerchant->fillMerchantPoolInfoFromRequest();
        $csMerchant->fillMerchantActiveInfoFromRequest();


        self::checkName($csMerchant);

        $dataBefore = $dataModify = $dataAfter = [];
        if (!empty($csMerchantOri)) {
            $dataBefore = $csMerchantOri->toArray();
        }
        $dataAfter  = $csMerchant->toArray();
        if($id!=0){
            if ($dataType == 'csMerchant') {
                $lastAudit = CsMerchantAudit::where('cs_merchant_id',$id)->where('status',CsMerchantAudit::AUDIT_STATUS_AUDITING)->first();
            } elseif ($dataType == 'audit' && !empty($merchantId)) {
                $lastAudit = CsMerchantAudit::where('cs_merchant_id',$merchantId)->where('status',CsMerchantAudit::AUDIT_STATUS_AUDITING)->first();
            } else {
                $lastAudit = CsMerchantAudit::find($id);
            }

            if($lastAudit&&$lastAudit->status==CsMerchantAudit::AUDIT_STATUS_AUDITING){
                throw new BaseResponseException('该商户尚有待审核记录，无法继续提交');
            }
        }else{
            $exist = CsMerchantAudit::where('name','like',$dataAfter['name'])->where('status',CsMerchantAudit::AUDIT_STATUS_AUDITING)->exists();
            if($exist){
                throw new BaseResponseException('该商户尚有待审核记录，无法继续提交');
            }
        }

        if(!empty($dataBefore)){
            // 存储变更数据
            foreach ($dataAfter as $k=>$v){
                if(isset($dataBefore[$k])&&($dataAfter[$k]!=$dataBefore[$k])){
                    $dataModify[$k] = $dataBefore[$k];
                }
            }
        }

        /*if($dataType=='csMerchant'||(isset($lastAudit->cs_merchant_id)&&$lastAudit->cs_merchant_id!=0)){
            // 有商户信息则走以下逻辑      运营中心审核不走改用户状态逻辑
            $csMerchant = ($csMerchant->id) ? $csMerchant:CsMerchant::find($lastAudit->cs_merchant_id);
            if($csMerchant){

                //编辑商户，商户编辑后是待审核
                CsMerchant::where('id',$csMerchant->id)->update(['audit_status' => CsMerchant::AUDIT_STATUS_AUDITING]);
                $csMerchant->audit_status = CsMerchant::AUDIT_STATUS_AUDITING;

            }
        }*/
        if($csMerchant->bank_card_type == CsMerchant::BANK_CARD_TYPE_COMPANY){
            if($dataAfter['name'] != $dataAfter['bank_open_name']){
                throw new ParamInvalidException('提交失败，申请T+1结算，商户名称需和开户名一致');
            }
        }elseif($csMerchant->bank_card_type == CsMerchant::BANK_CARD_TYPE_PEOPLE){
            if($dataAfter['corporation_name'] != $dataAfter['bank_open_name']){
                throw new ParamInvalidException('提交失败，申请T+1结算，营业执照及法人姓名需和开户名一致');
            }
        }
        $auditType = ($csMerchant->id)?CsMerchantAudit::UPDATE_TYPE:CsMerchantAudit::INSERT_TYPE;

        // 获取上条提交数据
        $lastAudit = $lastAudit??CsMerchantAudit::where('name','like',$dataAfter['name'])->orderBy('id','desc')->first();
        if($lastAudit){
            $dataBefore = json_decode($lastAudit->data_after,true);
            if($auditType==CsMerchantAudit::UPDATE_TYPE){
                $merchantId = $lastAudit->cs_merchant_id;
            }
        }



        $params = [
            'oper_id' => $operId,
            'type' => $auditType,
            'csMerchantId' => $merchantId,
            'name' => $dataAfter['name'],
            'dataBefore' => json_encode($dataBefore),
            'dataAfter' => json_encode($dataAfter),
            'dataModify' => json_encode($dataModify),
        ];

        // 添加审核记录
        DB::beginTransaction();
        try{
            DB::commit();
            if($draftId!=0){
                // 删除对应的草稿箱数据
                $merchantDraft = CsMerchantDraftService::getById($draftId);
                if(!empty($merchantDraft)){
                    $merchantDraft->delete();
                }
            }
            return CsMerchantAuditService::addAudit($params);
        }catch (\Exception $exception){
            DB::rollBack();
            throw $exception;
        }
    }

}
