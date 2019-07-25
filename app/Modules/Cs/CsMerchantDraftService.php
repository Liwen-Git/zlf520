<?php

namespace App\Modules\Cs;

use App\BaseModel;
use App\Exceptions\BaseResponseException;
use App\Exceptions\ParamInvalidException;
use App\Modules\Merchant\Merchant;
use App\Modules\Merchant\MerchantAccount;
use App\Modules\Oper\Oper;
use App\Modules\Oper\OperBizMember;
use Illuminate\Database\Eloquent\Builder;

class CsMerchantDraftService extends BaseModel
{
    //
    public static function add($currentOperId)
    {
        $merchantDraft = new CsMerchantDraft();
        $merchantDraft->fillMerchantPoolInfoFromRequest();
        $merchantDraft->fillMerchantActiveInfoFromRequest();
        // 判断是否存在重名
        self::checkDuplicationName('name',$merchantDraft->name,'该商户名重复，请修改');
        self::checkDuplicationName('signboard_name',$merchantDraft->signboard_name,'该商户招牌名重复，请修改。');
        $merchantDraft->oper_id = $currentOperId;
        $merchantDraft->save();

        return $merchantDraft;
    }

    /**
     * 根据ID获取商户信息
     * @param $merchantId
     * @param array|string $fields
     * @return CsMerchantDraft
     */
    public static function getById($merchantId, $fields = ['*'])
    {
        if(is_string($fields)){
            $fields = explode(',', $fields);
        }
        return CsMerchantDraft::find($merchantId, $fields);
    }

    /**
     * 判断是否重名
     * @param $columnName    string       查询字段名
     * @param $value         string       查询值
     * @param $exceptionMessage  string   报错信息
     */
    public static function checkDuplicationName($columnName,$value,$exceptionMessage){
        $existMerchant = Merchant::where($columnName,$value)->exists();
        if($existMerchant){
            throw new BaseResponseException($exceptionMessage);
        }
        if ($columnName == 'name') {
            $existMerchantAudit = CsMerchantAudit::where('name',$value)
                ->whereIn('status',[CsMerchantAudit::AUDIT_STATUS_AUDITING,CsMerchantAudit::AUDIT_STATUS_SUCCESS])
                ->exists();
            if($existMerchantAudit){
                throw new BaseResponseException($exceptionMessage);
            }
        }
        $existCsMerchant = CsMerchant::where($columnName,$value)->exists();
        if($existCsMerchant){
            throw new BaseResponseException($exceptionMessage);
        }

    }

    public static function getList($currentOperId,$status,$auditStatus,$name)
    {
        $data = CsMerchantDraft::where(function (Builder $query) use ($currentOperId){

            $query->where('oper_id', $currentOperId);
//                ->orWhere('audit_oper_id', $currentOperId);
        })
            ->when($status, function (Builder $query) use ($status){
                $query->where('status', $status);
            })
            ->when(!empty($auditStatus), function (Builder $query) use ($auditStatus){
                if($auditStatus == -1){
                    $auditStatus = 0;
                }
                $query->where('audit_status', $auditStatus);
            })
            ->when($name, function (Builder $query) use ($name){
                $query->where('name', 'like', "%$name%");
            })
            ->orderBy('updated_at', 'desc')
            ->paginate();

        $data->each(function ($item){
            /*if ($item->merchant_category_id){
                $item->categoryPath = CsMerchantCategoryService::getCategoryPath($item->merchant_category_id);
            }*/
            $item->desc_pic_list = $item->desc_pic_list ? explode(',', $item->desc_pic_list) : [];
            $item->account = MerchantAccount::where('merchant_id', $item->id)->first();
            $item->operBizMemberName = OperBizMember::where('oper_id', $item->oper_id)->where('code', $item->oper_biz_member_code)->value('name') ?: '无';
        });

        return $data;
    }

    public static function edit($id,$currentOperId)
    {
        $merchantDraft = CsMerchantDraft::where('id', $id)
            ->where('oper_id', $currentOperId)
            ->first();

        $merchantDraft = ($merchantDraft) ? $merchantDraft : new CsMerchantDraft();

        $merchantDraft->fillMerchantPoolInfoFromRequest();
        $merchantDraft->fillMerchantActiveInfoFromRequest();

        self::checkDuplicationName('name',$merchantDraft->name,'该商户名重复，请修改');
        self::checkDuplicationName('signboard_name',$merchantDraft->signboard_name,'该商户招牌名重复，请修改。');

        $merchantDraft->save();

        return $merchantDraft;
    }

    public static function detail($id)
    {
        $merchantDraft = CsMerchantDraft::findOrFail($id);
//        $merchantDraft->categoryPath = $merchantDraft->merchant_category_id ? MerchantCategoryService::getCategoryPath($merchantDraft->merchant_category_id) : [];
//        $merchantDraft->categoryPathOnlyEnable = $merchantDraft->merchant_category_id ? MerchantCategoryService::getCategoryPath($merchantDraft->merchant_category_id, true) : [];
//        $merchantDraft->account = MerchantAccount::where('merchant_id', $merchantDraft->id)->first();
        $merchantDraft->business_time = json_decode($merchantDraft->business_time);
        $oper = Oper::where('id', $merchantDraft->oper_id)->first();
        if ($oper) {
            $merchantDraft->operAddress = $oper->province . $oper->city . $oper->area . $oper->address;
            $merchantDraft->isPayToPlatform = in_array($oper->pay_to_platform, [Oper::PAY_TO_PLATFORM_WITHOUT_SPLITTING, Oper::PAY_TO_PLATFORM_WITH_SPLITTING]);
        }

        return $merchantDraft;
    }
}
