<template>
    <page title="人工生成结算单">
        <el-form inline :model="query" size="small">
            <el-form-item label="商户ID">
                <el-input type="text" clearable placeholder="请输入商户ID" v-model="query.merchant_id" class="w-150"/>
            </el-form-item>
            <el-form-item>
                <el-button type="primary" @click="search">
                    <i class="el-icon-search"></i> 搜索
                </el-button>
            </el-form-item>
        </el-form>
        <el-table :data="list" stripe v-loading="isLoading">
            <el-table-column prop="merchant_id" label="商户ID"/>
            <el-table-column prop="merchant_name" label="商户名称"/>
            <el-table-column prop="merchant_type" label="商户类型">
                <template slot-scope="scope">
                    <span v-if="scope.row.merchant_type === 1">普通商户</span>
                    <span v-else-if="scope.row.merchant_type === 2">超市商户</span>
                </template>
            </el-table-column>
            <el-table-column prop="settlement_cycle_type" label="结算周期">
                <template slot-scope="scope">
                    <span>{{ {1: '周结', 2: '半月结', 3: 'T+1(自动)', 4: '半年结', 5: '年结', 6: 'T+1(人工)', 7: '未知',}[scope.row.settlement_cycle_type] }}</span>
                </template>
            </el-table-column>
            <el-table-column prop="oper_name" label="运营中心名称"/>
            <el-table-column prop="amount" label="未结算订单金额"/>
            <el-table-column label="操作" width="200px">
                <template slot-scope="scope">
                    <el-button v-show="scope.row.amount>0" type="text" @click="settlement(scope)">确认强制生成结算单</el-button>
                </template>
            </el-table-column>
        </el-table>

    </page>
</template>

<script>
    import api from '../../../assets/js/api'

    export default {
        name: "manual_settlement",
        data(){
            return {
                isLoading: false,
                query: {
                    merchant_id: '',
                },
                list: [],
            }
        },
        methods: {
            settlement(scope) {
                this.$confirm('确认强制生成结算单吗？请谨慎操作', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    let queryData = {'merchant_id':scope.row.merchant_id};
                    api.post('/settlement/manualGen', queryData).then(data => {
                        if (data.list) {
                            this.$confirm('结算单生成成功，立即查看', {
                                confirmButtonText: '确定',
                                cancelButtonText: '取消',
                                type: 'warning'
                            }).then(() => {
                                if (data.list.merchant_type == 1) {
                                    this.$menu.change('/settlement/allPlatforms', {settlement_no: data.list.settlement_no})
                                } else {
                                    this.$menu.change('/settlement/csPlatforms', {settlement_no: data.list.settlement_no})
                                }
                            }).catch(() => {

                                this.search();
                            });

                        }
                        console.log(data.list)
                    }).catch(() => {

                    }).finally(() => {

                    })
                }).catch(() => {

                });

            },

            search() {
                this.isLoading = true;
                let queryData = deepCopy(this.query);
                api.get('/settlement/manualSearch', queryData).then(data => {
                    this.isLoading = false;
                    this.list = data.list;
                    this.total = data.total;
                }).catch(() => {
                    this.isLoading = false;
                }).finally(() => {
                    this.isLoading = false;
                })
            }



        }
    }
</script>

<style scoped>

</style>