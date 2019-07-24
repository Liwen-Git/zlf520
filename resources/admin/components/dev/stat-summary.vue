<template>
    <page title="平台汇总统计">
        <el-row>
            <el-table :data="[summary]" class="table">
                <el-table-column label="用户" align="center">
                    <el-table-column label="用户量" prop="userCount"></el-table-column>
                </el-table-column>
            </el-table>
            <el-table :data="[summary]" class="table">
                <el-table-column label="订单" align="center">
                    <el-table-column label="订单量(包含退款)" prop="orderPaidCount"></el-table-column>
                    <el-table-column label="订单量(不包含退款)" prop="orderUnrefundCount"></el-table-column>
                    <el-table-column label="完成订单量" prop="orderFinishCount"></el-table-column>
                    <el-table-column label="总流水(包含退款)" prop="orderPaidAmount"></el-table-column>
                    <el-table-column label="净流水(不包含退款)" prop="orderUnrefundAmount"></el-table-column>
                    <el-table-column label="完结订单金额" prop="orderFinishAmount"></el-table-column>
                </el-table-column>
            </el-table>
            <el-table :data="[summary]" class="table">
                <el-table-column label="商户" align="center">
                    <el-table-column label="普通商户(不包含试点商户)" align="center">
                        <el-table-column label="总数量" prop="merchantCount"></el-table-column>
                        <el-table-column label="未禁用数量" prop="merchantEnabledCount"></el-table-column>
                        <el-table-column label="审核通过数量" prop="merchantAuditCount"></el-table-column>
                        <el-table-column label="未禁用审核通过数量" prop="merchantEnabledAuditCount"></el-table-column>
                    </el-table-column>
                    <el-table-column label="试点商户" align="center">
                        <el-table-column label="总数量" prop="pilotMerchantCount"></el-table-column>
                        <el-table-column label="未禁用数量" prop="pilotMerchantEnabledCount"></el-table-column>
                        <el-table-column label="审核通过数量" prop="pilotMerchantAuditCount"></el-table-column>
                        <el-table-column label="未禁用审核通过数量" prop="pilotMerchantEnabledAuditCount"></el-table-column>
                    </el-table-column>
                    <el-table-column label="超市" align="center">
                        <el-table-column label="总数量" prop="csMerchantCount"></el-table-column>
                        <el-table-column label="未禁用数量" prop="csMerchantEnabledCount"></el-table-column>
                        <el-table-column label="审核通过数量" prop="csMerchantAuditCount"></el-table-column>
                        <el-table-column label="未禁用审核通过数量" prop="csMerchantEnabledAuditCount"></el-table-column>
                    </el-table-column>
                </el-table-column>
            </el-table>
            <el-table :data="[summary]" class="table">
                <el-table-column label="运营中心" align="center">
                    <el-table-column label="运营中心总数" prop="operCount"></el-table-column>
                </el-table-column>
            </el-table>
        </el-row>
    </page>
</template>

<script>
    import api from '../../../assets/js/api'
    export default {
        name: "stat-summary",
        data() {
            return {
                summary: {
                    userCount: '',
                    orderPaidCount: '',
                    orderUnrefundCount: '',
                    orderFinishCount: '',
                    orderPaidAmount: '',
                    orderUnrefundAmount: '',
                    orderFinishAmount: '',
                    merchantCount: '',
                    merchantEnabledCount: '',
                    merchantAuditCount: '',
                    merchantEnabledAuditCount: '',
                    pilotMerchantCount: '',
                    pilotMerchantEnabledCount: '',
                    pilotMerchantAuditCount: '',
                    pilotMerchantEnabledAuditCount: '',
                    csMerchantCount: '',
                    csMerchantEnabledCount: '',
                    csMerchantAuditCount: '',
                    csMerchantEnabledAuditCount: '',
                    operCount: '',
                }
            }
        },
        methods: {
            getData(){
                api.get('dev/stat/summary').then(data => {
                    this.summary = data;
                })
            }
        },
        created(){
            this.getData();
        }
    }
</script>

<style scoped>
    .table {
        margin-bottom: 50px;
    }
</style>