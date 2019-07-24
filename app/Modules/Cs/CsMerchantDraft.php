<?php

namespace App\Modules\Cs;

use App\BaseModel;
use Carbon\Carbon;
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
 */

class CsMerchantDraft extends BaseModel
{
    use CsMerchantTrait;
    //
}
