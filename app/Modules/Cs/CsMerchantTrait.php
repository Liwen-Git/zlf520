<?php
namespace App\Modules\Cs;
use App\Modules\Area\Area;

/**
 * 用以抽离填充方法
 * Trait CsMerchantTrait
 */
trait CsMerchantTrait{
    /**
     * 从请求中获取商户激活需要的数据, 并填充到当前实例中
     */
    public function fillMerchantActiveInfoFromRequest()
    {
        $this->brand = request('brand','');
        $this->invoice_title = request('invoice_title','');
        $this->invoice_no = request('invoice_no','');
        $this->status = request('status', 1);
        $this->business_time = request('business_time', '');
        $this->logo = request('logo','');
        $descPicList = request('desc_pic_list', '');
        $this->desc_pic_list = is_array($descPicList) ? implode(',', $descPicList) : $descPicList;
        $this->desc = request('desc','');
        $this->settlement_cycle_type = request('settlement_cycle_type', 1);
        $this->settlement_rate = request('settlement_rate', 0.00);
        // 银行卡信息
        $this->bank_card_type = request('bank_card_type', 1);
        $this->bank_name = request('bank_name','');
        $this->bank_open_name = request('bank_open_name','');
        $this->bank_card_no = request('bank_card_no','');
        $bankProvinceId = request('bank_province_id', 0);
        $bankCityId = request('bank_city_id', 0);
        $bankAreaId = request('bank_area_id', 0);
        $this->bank_province = $bankProvinceId ? Area::getNameByAreaId($bankProvinceId) : '';
        $this->bank_province_id = $bankProvinceId;
        $this->bank_city = $bankCityId ? Area::getNameByAreaId($bankCityId) : '';
        $this->bank_city_id = $bankCityId;
        $this->bank_area = $bankAreaId ? Area::getNameByAreaId($bankAreaId) : '';
        $this->bank_area_id = $bankAreaId;
        $this->sub_bank_name = request('sub_bank_name','');
        $this->bank_open_address = request('bank_open_address','');
        $bankCardPicA = request('bank_card_pic_a','');
        if (is_array($bankCardPicA)) $bankCardPicA = implode(',', $bankCardPicA);
        $this->bank_card_pic_a = $bankCardPicA;

        $licence_pic_url = request('licence_pic_url','');
        if (is_array($licence_pic_url)) $licence_pic_url = implode(',', $licence_pic_url);
        $this->licence_pic_url = $licence_pic_url;

        $this->legal_id_card_pic_a = request('legal_id_card_pic_a','');
        $this->legal_id_card_pic_b = request('legal_id_card_pic_b','');
        $this->country_id = request('country_id', 0);
        $this->corporation_name = request('corporation_name', '');
        $this->legal_id_card_num = request('legal_id_card_num','');
        $this->business_licence_pic_url = request('business_licence_pic_url','');
        $this->organization_code = request('organization_code','');
        $contractPicUrl = request('contract_pic_url','');
        if (is_array($contractPicUrl)) $contractPicUrl = implode(',', $contractPicUrl);
        $this->contract_pic_url = $contractPicUrl;
        $otherCardPicUrls = request('other_card_pic_urls', '');
        if(is_array($otherCardPicUrls)) $otherCardPicUrls = implode(',', $otherCardPicUrls);
        $this->other_card_pic_urls = $otherCardPicUrls;
        // 商户负责人
        $this->contacter = request('contacter','');
        $this->contacter_phone = request('contacter_phone','');
        $this->service_phone = request('service_phone','');

    }


    /**
     * 从请求中获取商户池数据, 并填充到当前实例中
     */
    public function fillMerchantPoolInfoFromRequest()
    {
        // 商户基本信息
        $this->name = request('name');
        $this->signboard_name = request('signboard_name', '');

        // 位置信息
        $provinceId = request('province_id', 0);
        $cityId = request('city_id', 0);
        $areaId = request('area_id', 0);
        $this->province = $provinceId ? Area::getNameByAreaId($provinceId) : '';
        $this->province_id = $provinceId;
        $this->city = $cityId ? Area::getNameByAreaId($cityId) : '';
        $this->city_id = $cityId;
        $this->area = $areaId ? Area::getNameByAreaId($areaId) : '';
        $this->area_id = $areaId;
        $this->lng = request('lng',0);
        $this->lat = request('lat',0);
        $this->address = request('address','');
    }
}
