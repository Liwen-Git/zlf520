<?php

namespace App\Modules\Cs;

use App\BaseModel;
use App\Modules\Merchant\MerchantFollow;
use App\Modules\Oper\Oper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CsMerchant
 * @package App\Modules\CsMerchant
 *
 * @property int    oper_id
 * @property int    merchant_category_id
 * @property string name
 * @property string brand
 * @property string signboard_name
 * @property int region
 * @property string province
 * @property int province_id
 * @property string city
 * @property int city_id
 * @property string area
 * @property int area_id
 * @property int business_time
 * @property string logo
 * @property string desc_pic
 * @property string desc_pic_list
 * @property string desc
 * @property string invoice_title
 * @property string invoice_no
 * @property int status
 * @property number lng
 * @property number lat
 * @property string address
 * @property string contacter
 * @property string contacter_phone
 * @property int settlement_cycle_type
 * @property number settlement_rate
 * @property string business_licence_pic_url
 * @property string organization_code
 * @property string tax_cert_pic_url
 * @property string legal_id_card_pic_a
 * @property string legal_id_card_pic_b
 * @property integer country_id
 * @property string corporation_name
 * @property string legal_id_card_num
 * @property string contract_pic_url
 * @property string hygienic_licence_pic_url
 * @property string agreement_pic_url
 * @property int bank_card_type
 * @property string bank_open_name
 * @property string bank_card_no
 * @property string bank_name
 * @property string sub_bank_name
 * @property string bank_province
 * @property integer bank_province_id
 * @property string bank_city
 * @property integer bank_city_id
 * @property string bank_area
 * @property integer bank_area_id
 * @property string bank_open_address
 * @property int audit_status
 * @property string audit_suggestion
 * @property string licence_pic_url
 * @property int creator_oper_id
 * @property string service_phone
 * @property string bank_card_pic_a
 * @property string other_card_pic_urls
 * @property string oper_salesman
 * @property string oper_biz_member_code
 * @property Carbon active_time
 * @property Carbon first_active_time
 * @property number lowest_amount
 * @property int mapping_user_id
 * @property int level
 * @property int is_pilot
 * @property integer bizer_id
 * @property int user_follows
 * @property int hot_status
 * @property string  hot_add_time
 * @property int hot_goods_count
 */
class CsMerchant extends BaseModel
{
    use SoftDeletes;
    use CsMerchantTrait;

    /**
     * 需要转换成日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    //
    /**
     * 未审核(审核中)
     */
    const AUDIT_STATUS_AUDITING = 0;
    /**
     * 审核通过
     */
    const AUDIT_STATUS_SUCCESS = 1;
    /**
     * 审核不通过
     */
    const AUDIT_STATUS_FAIL = 2;
    /**
     * 重新提交审核
     */
    const AUDIT_STATUS_RESUBMIT = 3;
    /**
     * 审核不通过并且打回到商户池, 审核记录中才有该状态, 商家信息中直接置位审核不通过
     */
//    const AUDIT_STATUS_FAIL_TO_POOL = 4;
    /**
     * 取消审核
     */
//    const AUDIT_STATUS_CANCEL = 5;

    /**
     * 结算类型
     */
    const SETTLE_WEEKLY = 1; // 周结
    const SETTLE_HALF_MONTHLY = 2; // 半月结
    const SETTLE_DAILY_AUTO = 3; // T+1（自动）
    const SETTLE_HALF_YEARLY = 4; // 半年结
    const SETTLE_YEARLY = 5; // 年结
    const SETTLE_DAY_ADD_ONE = 6; // T+1（人工）

    /**
     * 试点商户
     */
    const PILOT_MERCHANT = 1;
    const NORMAL_MERCHANT = 0;

    /**
     * 商户状态
     */
    const STATUS_ON = 1;
    const STATUS_OFF = 2;

    /**
     * 银行账户类型 1-公司账户 2-个人账户
     */
    const BANK_CARD_TYPE_COMPANY = 1;
    const BANK_CARD_TYPE_PEOPLE = 2;


    const HOT_STATUS_ON = 1;//年货节活动上架
    const HOT_STATUS_OFF = 0;//年货节活动下架


    public static function allStatus()
    {
        return [self::STATUS_ON=>'启用',self::STATUS_OFF=>'冻结'];
    }

    public static function statusName(int $status)
    {

        $all_status = self::allStatus();
        return $all_status[$status]??'';
    }

    public static function allAuditStatus()
    {
        return [
            self::AUDIT_STATUS_AUDITING=>'审核中',
            self::AUDIT_STATUS_SUCCESS=>'审核通过',
            self::AUDIT_STATUS_FAIL=>'审核不通过',
            self::AUDIT_STATUS_RESUBMIT=>'重新提交审核'
        ];
    }

    public static function auditStatusName(int $audit_status)
    {

        $all = self::allAuditStatus();
        return $all[$audit_status]??'';
    }

    public static function allHotStatus()
    {
        return [self::HOT_STATUS_ON=>'上架',self::HOT_STATUS_OFF=>'下架'];
    }

    public static function hotStatusName(int $hot_status)
    {
        $all_status = self::allHotStatus();
        return $all_status[$hot_status]??'';
    }

    /**
     * 获取商户等级描述
     * @param $level
     * @return mixed
     */
    public static function getLevelText($level)
    {
        return ['', '签约商户', '联盟商户', '品牌商户'][$level];
    }

    public function oper()
    {
        return $this->belongsTo(Oper::class);
    }

    public function merchantFollow(){
        return $this->hasMany(MerchantFollow::class, 'merchant_id');
    }

    public function csStatisticsMerchantOrder()
    {
        return $this->hasOne(CsMerchant::class);
    }

    public function csGoods()
    {
        return $this->hasMany(CsGood::class);
    }


    /**
     * 获取商户状态
     * @param $auditStatus
     * @param $status
     * @return string
     */
    public static function getMerchantStatusText($auditStatus,$status){
        if(in_array($auditStatus,[1,3])){
            if($status == 1){
                $statusText = '正常';
            }elseif($status == 2){
                $statusText = '冻结';
            }else{
                $statusText = '';
            }
        }else{
            $statusText = '';
        }
        return $statusText;
    }

    public function goods()
    {
        return $this->hasMany(CsGood::Class,'id','cs_merchant_id');
    }



    /*protected $dispatchesEvents = [
        'created' => \App\Events\MerchantCreatedEvent::class,
    ];*/

}
