<?php

namespace App\Console\Commands\Updates;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class V1_5_1 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:v1.5.1';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sqlList = [
            /*"ALTER TABLE `cs_merchant_audits` ADD `is_draft` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '是否为草稿 0：否 1：是' AFTER `status`,
              ADD index `cs_merchant_audits_is_draft` (`is_draft`)
            ",*/
            "CREATE TABLE `cs_merchants_draft` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oper_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属运营中心ID',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商家名称',
  `brand` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商家品牌',
  `signboard_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商家招牌名称',
  `region` tinyint(4) NOT NULL DEFAULT '1' COMMENT '运营地区/大区 1-中国 2-美国 3-韩国 4-香港',
  `province` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所在省份',
  `province_id` int(11) NOT NULL DEFAULT '0' COMMENT '所在省份Id',
  `city` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所在城市',
  `city_id` int(11) NOT NULL DEFAULT '0' COMMENT '所在城市Id',
  `area` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所在县区',
  `area_id` int(11) NOT NULL DEFAULT '0' COMMENT '所在县区Id',
  `business_time` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '营业时间, json格式字符串 {startTime, endTime}',
  `logo` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商家logo',
  `desc_pic` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商家介绍图片 [保留, 使用desc_pic_list]',
  `desc_pic_list` varchar(2000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商家介绍图片列表  多图, 使用逗号分隔 ',
  `desc` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商家介绍',
  `invoice_title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发票抬头 [废弃, 使用商户名或营业执照图片中的公司名]',
  `invoice_no` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发票税号 [废弃, 同商户营业执照编号]',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 1-正常 2-禁用',
  `lng` decimal(15,12) NOT NULL DEFAULT '0.000000000000' COMMENT '商家所在经度',
  `lat` decimal(15,12) NOT NULL DEFAULT '0.000000000000' COMMENT '商家所在纬度',
  `address` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商家地址',
  `contacter` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '负责人姓名',
  `contacter_phone` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '负责人联系方式',
  `settlement_cycle_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '结款周期 1-周结 2-半月结 3-T+1（自动） 4-半年结 5-年结 6-T+1（人工）',
  `settlement_rate` decimal(4,2) NOT NULL DEFAULT '0.00' COMMENT '分利比例(结算时的费率)',
  `business_licence_pic_url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '营业执照',
  `organization_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '组织机构代码, 即营业执照代码',
  `tax_cert_pic_url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '税务登记证',
  `legal_id_card_pic_a` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '法人身份证正面',
  `legal_id_card_pic_b` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '法人身份证反面',
  `country_id` int(11) NOT NULL DEFAULT '1' COMMENT '国别或地区ID',
  `corporation_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '法人姓名',
  `legal_id_card_num` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '法人身份证号码',
  `contract_pic_url` varchar(2000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '合同照片, 多张图片使用逗号分隔',
  `licence_pic_url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户许可证',
  `hygienic_licence_pic_url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '卫生许可证',
  `agreement_pic_url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '协议文件',
  `bank_card_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '银行账户类型 1-公司账户 2-个人账户',
  `bank_open_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '银行开户名',
  `bank_card_no` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '银行账号',
  `bank_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户行',
  `sub_bank_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户支行名称',
  `bank_province` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户行省份',
  `bank_province_id` int(11) NOT NULL DEFAULT '0' COMMENT '开户行省份id',
  `bank_city` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户行城市',
  `bank_city_id` int(11) NOT NULL DEFAULT '0' COMMENT '开户行城市id',
  `bank_area` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户行县区',
  `bank_area_id` int(11) NOT NULL DEFAULT '0' COMMENT '开户行县区id',
  `bank_open_address` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户支行地址',
  `audit_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '商户资料审核状态 0-未审核 1-已审核 2-审核不通过 3-重新提交审核',
  `audit_suggestion` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '审核意见',
  `service_phone` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客服电话',
  `bank_card_pic_a` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '法人银行卡正面照',
  `other_card_pic_urls` varchar(2000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '其他证件照片',
  `oper_salesman` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '运营中心业务人员姓名',
  `oper_biz_member_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '运营中心业务员推荐码',
  `active_time` timestamp NULL DEFAULT NULL COMMENT '最近激活时间, 即商户最近一次审核通过时间',
  `first_active_time` timestamp NULL DEFAULT NULL COMMENT '首次审核通过时间',
  `mapping_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '商户关联的user_id',
  `level` tinyint(4) NOT NULL DEFAULT '1' COMMENT '商户等级 1-签约商户 2-联盟商户 3-品牌商户',
  `lowest_amount` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '最低消费',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cs_merchants_oper_id_index` (`oper_id`),
  KEY `cs_merchants_region_index` (`region`),
  KEY `cs_merchants_province_id_index` (`province_id`),
  KEY `cs_merchants_city_id_index` (`city_id`),
  KEY `cs_merchants_area_id_index` (`area_id`),
  KEY `cs_merchants_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='超市商户审核草稿箱表'"
        ];
        foreach ($sqlList as $sql){
            DB::statement($sql);
        }
        $this->info('更新表结构完成');
    }
}
