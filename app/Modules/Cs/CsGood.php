<?php

namespace App\Modules\Cs;

use App\BaseModel;
use App\Modules\CsOrder\CsOrderGood;
use App\Modules\Oper\Oper;

/**
 * 超市商品类
 * Class CsGood
 * @package App\Modules\Cs
 * @property integer  id
 * @property integer  oper_id
 * @property integer cs_merchant_id
 * @property integer cs_platform_cat_id_level1
 * @property integer cs_platform_cat_id_level2
 * @property string goods_name
 * @property float market_price
 * @property float price
 * @property integer stock
 * @property integer sale_num
 * @property string logo
 * @property string detail_imgs
 * @property string summary
 * @property string certificate1
 * @property string certificate2
 * @property string certificate3
 * @property integer status
 * @property integer audit_status
 * @property string audit_suggestion
 * @property integer sort
 * @property string hot_add_time
 * @property integer hot_status
 * @property integer hot_sort
 * @property integer hot_total_sort
 * @property integer saas_audit_status
 * @property float sort_price
 * @property int is_in_markting
 * @property int sale_count_30d
 *
 */
class CsGood extends BaseModel
{
    //
    const STATUS_ON = 1; //上架
    const STATUS_OFF = 2; //下架

    const AUDIT_STATUS_AUDITING = 1; //审核中
    const AUDIT_STATUS_SUCCESS = 2; //审核通过
    const AUDIT_STATUS_FAIL = 3; //审核不通过

    const HOT_STATUS_ON = 1; //年货节上架
    const HOT_STATUS_OFF = 0; //年货节下架

    //saas抽检状态：0：未抽检 1：抽检通过 2：抽检不通过
    const SAAS_AUDIT_STATUS_NOT = 0;
    const SAAS_AUDIT_STATUS_SCUUESS = 1;
    const SAAS_AUDIT_STATUS_FAIL = 2;

    public function cs_merchant()
    {
        return $this->belongsTo(CsMerchant::class);
    }

    public function oper()
    {
        return $this->belongsTo(Oper::class);
    }

    public function cs_order_goods()
    {
        return $this->hasMany(CsOrderGood::class, 'cs_goods_id');
    }

    public static function allStatus()
    {
        return [self::STATUS_ON=>'上架',self::STATUS_OFF=>'下架'];
    }

    public static function statusName(int $status)
    {

        $all_status = self::allStatus();
        return $all_status[$status]??'';
    }

    public static function allAuditStatus()
    {
        return [self::AUDIT_STATUS_AUDITING=>'审核中',self::AUDIT_STATUS_SUCCESS=>'审核通过',self::AUDIT_STATUS_FAIL=>'审核不通过'];
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
        $all = self::allHotStatus();
        return $all[$hot_status]??'';
    }
}
